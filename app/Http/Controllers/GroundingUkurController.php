<?php
namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Concerns\SimpanFotoKeterangan;
use App\Models\GroundingTitik;
use App\Models\GroundingUkur;
use App\Models\GroundingWilayah;
use App\Models\PerawatanRutin;
use App\Models\PerawatanRutinLog;
use App\Support\Akses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Pencatatan hasil pengukuran: per titik dan satu ronde per wilayah. */
class GroundingUkurController extends Controller
{
    use SimpanFotoKeterangan;

    private function masuk(): void
    {
        if (!Akses::groundingMasuk(auth()->user())) abort(403, 'Menu ini hanya untuk Divisi Sarana & Prasarana.');
    }


    /** Pengukuran baru memajukan acuan jadwal Perawatan Rutin "grounding" untuk titik tsb (hanya maju, tidak mundur). */
    private function sinkronJadwal(GroundingUkur $u): void
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasColumn('perawatan_rutin', 'grounding_titik_id')) return;
            $tgl = $u->tanggal->format('Y-m-d');
            foreach (PerawatanRutin::where('objek_tipe', 'grounding')->where('grounding_titik_id', $u->titik_id)->where('aktif', true)->get() as $j) {
                PerawatanRutinLog::create([
                    'perawatan_rutin_id' => $j->id, 'user_id' => $u->user_id, 'tanggal' => $tgl,
                    'catatan' => 'Pengukuran grounding: '.rtrim(rtrim(number_format((float) $u->nilai_ohm, 2, ',', ''), '0'), ',')." \u{03A9} (".GroundingUkur::STATUS_LABEL[$u->status].')',
                ]);
                if (!$j->terakhir_tanggal || $tgl >= $j->terakhir_tanggal->format('Y-m-d')) {
                    $j->terakhir_tanggal = $tgl;
                    $j->save();
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('Sinkron jadwal grounding gagal: '.$e->getMessage());
        }
    }

    private function metaRules(): array
    {
        return [
            'tanggal' => 'required|date|before_or_equal:today',
            'jam' => 'nullable|date_format:H:i',
            'kondisi_tanah' => 'nullable|in:kering,lembab,basah',
            'alat' => 'nullable|string|max:80',
            'metode' => 'nullable|string|max:60',
        ];
    }

    public function create(GroundingTitik $groundingTitik)
    {
        $this->masuk();
        $groundingTitik->load(['wilayah', 'ukurTerakhir']);
        return view('grounding.ukur-form', ['t' => $groundingTitik]);
    }

    public function store(Request $request, GroundingTitik $groundingTitik)
    {
        $this->masuk();
        $this->normalisasiAngka($request, ['nilai_ohm']);
        $v = $request->validate($this->metaRules() + [
            'nilai_ohm' => 'required|numeric|min:0|max:1000000',
            'keterangan' => 'nullable|string|max:2000',
            'fotos' => 'nullable|array|max:10', 'fotos.*' => 'image|mimes:jpeg,png,jpg,webp|max:8192',
            'foto_keterangan' => 'nullable|array', 'foto_keterangan.*' => 'nullable|string|max:255',
        ]);
        $u = GroundingUkur::create([
            'titik_id' => $groundingTitik->id, 'user_id' => auth()->id(),
            'tanggal' => $v['tanggal'], 'jam' => $v['jam'] ?? null,
            'nilai_ohm' => $v['nilai_ohm'], 'batas_ohm' => $groundingTitik->batas_ohm,
            'status' => GroundingUkur::hitungStatus((float) $v['nilai_ohm'], (float) $groundingTitik->batas_ohm),
            'kondisi_tanah' => $v['kondisi_tanah'] ?? null, 'alat' => $v['alat'] ?? null, 'metode' => $v['metode'] ?? null,
            'keterangan' => $v['keterangan'] ?? null,
        ]);
        $this->sinkronJadwal($u);
        $gagal = $this->simpanFotoRelasi($request, $u->fotos(), 'grounding/ukur', 'foto_keterangan', 0);
        $r = redirect()->route('grounding.titik.show', $groundingTitik)
            ->with('success', 'Pengukuran dicatat: '.rtrim(rtrim(number_format((float) $u->nilai_ohm, 2, ',', ''), '0'), ',')." \u{03A9} (".GroundingUkur::STATUS_LABEL[$u->status].').');
        return $gagal ? $r->with('warning', "$gagal foto gagal diunggah.") : $r;
    }

    public function ronde(GroundingWilayah $groundingWilayah)
    {
        $this->masuk();
        $groundingWilayah->load(['titiks' => fn($q) => $q->where('status', 'aktif')->orderBy('kode'), 'titiks.ukurTerakhir']);
        return view('grounding.ronde', ['w' => $groundingWilayah]);
    }

    public function rondeStore(Request $request, GroundingWilayah $groundingWilayah)
    {
        $this->masuk();
        $titiks = $groundingWilayah->titiks()->where('status', 'aktif')->get()->keyBy('id');

        $nilai = [];
        foreach ((array) $request->input('nilai', []) as $id => $val) {
            $val = is_string($val) ? str_replace(',', '.', trim($val)) : $val;
            if ($val === '' || $val === null) continue;
            $nilai[(int) $id] = $val;
        }
        $request->merge(['nilai' => $nilai]);

        $v = $request->validate($this->metaRules() + [
            'nilai' => 'required|array|min:1',
            'nilai.*' => 'numeric|min:0|max:1000000',
            'ket' => 'nullable|array', 'ket.*' => 'nullable|string|max:1000',
        ], ['nilai.required' => 'Isi nilai tahanan minimal untuk satu titik.', 'nilai.min' => 'Isi nilai tahanan minimal untuk satu titik.',
            'nilai.*.numeric' => 'Nilai tahanan harus berupa angka.']);

        $dibuat = 0; $buruk = 0;
        DB::transaction(function () use ($v, $nilai, $titiks, $request, &$dibuat, &$buruk) {
            foreach ($nilai as $id => $n) {
                $t = $titiks->get($id);
                if (!$t) continue;
                $status = GroundingUkur::hitungStatus((float) $n, (float) $t->batas_ohm);
                $baru = GroundingUkur::create([
                    'titik_id' => $t->id, 'user_id' => auth()->id(), 'tanggal' => $v['tanggal'], 'jam' => $v['jam'] ?? null,
                    'nilai_ohm' => $n, 'batas_ohm' => $t->batas_ohm, 'status' => $status,
                    'kondisi_tanah' => $v['kondisi_tanah'] ?? null, 'alat' => $v['alat'] ?? null, 'metode' => $v['metode'] ?? null,
                    'keterangan' => trim((string) $request->input("ket.$id")) ?: null,
                ]);
                $this->sinkronJadwal($baru);
                $dibuat++;
                if ($status !== 'baik') $buruk++;
            }
        });
        if ($dibuat === 0) return back()->withErrors(['nilai' => 'Tidak ada titik valid yang terisi.'])->withInput();

        return redirect()->route('grounding.wilayah.show', $groundingWilayah)
            ->with('success', "Ronde tersimpan: $dibuat titik diukur".($buruk ? ", $buruk perlu perhatian (waspada/melebihi batas)." : '.'));
    }


    private function bolehUbah(GroundingUkur $u): void
    {
        if (!Akses::groundingUkurHapus(auth()->user(), $u->user_id)) abort(403, 'Anda hanya dapat mengubah pengukuran milik sendiri.');
    }

    public function edit(GroundingUkur $groundingUkur)
    {
        $this->masuk();
        $this->bolehUbah($groundingUkur);
        $groundingUkur->load(['titik.wilayah', 'fotos']);
        return view('grounding.ukur-form', ['t' => $groundingUkur->titik, 'ukur' => $groundingUkur]);
    }

    public function update(Request $request, GroundingUkur $groundingUkur)
    {
        $this->masuk();
        $this->bolehUbah($groundingUkur);
        $this->normalisasiAngka($request, ['nilai_ohm']);
        $v = $request->validate($this->metaRules() + [
            'nilai_ohm' => 'required|numeric|min:0|max:1000000',
            'keterangan' => 'nullable|string|max:2000',
            'fotos' => 'nullable|array|max:10', 'fotos.*' => 'image|mimes:jpeg,png,jpg,webp|max:8192',
            'foto_keterangan_baru' => 'nullable|array', 'foto_keterangan_baru.*' => 'nullable|string|max:255',
            'foto_keterangan_lama' => 'nullable|array', 'foto_keterangan_lama.*' => 'nullable|string|max:255',
            'hapus_foto' => 'nullable|array', 'hapus_foto.*' => 'integer',
        ]);
        // batas yang berlaku saat pengukuran (salinan) dipertahankan agar riwayat konsisten
        $groundingUkur->update([
            'tanggal' => $v['tanggal'], 'jam' => $v['jam'] ?? null, 'nilai_ohm' => $v['nilai_ohm'],
            'status' => GroundingUkur::hitungStatus((float) $v['nilai_ohm'], (float) $groundingUkur->batas_ohm),
            'kondisi_tanah' => $v['kondisi_tanah'] ?? null, 'alat' => $v['alat'] ?? null, 'metode' => $v['metode'] ?? null,
            'keterangan' => $v['keterangan'] ?? null,
        ]);
        foreach ((array) $request->input('foto_keterangan_lama', []) as $id => $ket) {
            $groundingUkur->fotos()->where('id', (int) $id)->update(['keterangan' => $this->bersihKet($ket)]);
        }
        foreach ((array) $request->input('hapus_foto', []) as $id) {
            $f = $groundingUkur->fotos()->where('id', (int) $id)->first();
            if ($f) { ImageHelper::deleteWithThumbnail($f->path); $f->delete(); }
        }
        $gagal = $this->simpanFotoRelasi($request, $groundingUkur->fotos(), 'grounding/ukur', 'foto_keterangan_baru',
            (int) ($groundingUkur->fotos()->max('urutan') ?? -1) + 1);
        $r = redirect()->route('grounding.titik.show', $groundingUkur->titik_id)->with('success', 'Pengukuran diperbarui.');
        return $gagal ? $r->with('warning', "$gagal foto gagal diunggah.") : $r;
    }

    public function destroy(GroundingUkur $groundingUkur)
    {
        $this->masuk();
        if (!Akses::groundingUkurHapus(auth()->user(), $groundingUkur->user_id)) abort(403, 'Anda hanya dapat menghapus pengukuran milik sendiri.');
        $tid = $groundingUkur->titik_id;
        foreach ($groundingUkur->fotos as $f) ImageHelper::deleteWithThumbnail($f->path);
        $groundingUkur->delete();
        return redirect()->route('grounding.titik.show', $tid)->with('success', 'Pengukuran dihapus.');
    }
}
