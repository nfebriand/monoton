<?php
namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Concerns\SimpanFotoKeterangan;
use App\Models\GroundingTitik;
use App\Models\GroundingWilayah;
use App\Models\PerawatanRutin;
use App\Support\Akses;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Titik grounding: posisi di peta, spesifikasi, foto. */
class GroundingTitikController extends Controller
{
    use SimpanFotoKeterangan;

    private const ANGKA = ['pos_x','pos_y','latitude','longitude','kedalaman_m','diameter_mm','panjang_kabel_m','ukuran_kabel_mm2','batas_ohm'];

    private function masuk(): void
    {
        if (!Akses::groundingMasuk(auth()->user())) abort(403, 'Menu ini hanya untuk Divisi Sarana & Prasarana.');
    }

    private function kelola(): void
    {
        $this->masuk();
        if (!Akses::groundingKelola(auth()->user())) abort(403, 'Hanya admin Sarana & Prasarana yang dapat mengelola titik grounding.');
    }

    private function rules(int $wilayahId, ?int $ignoreId = null): array
    {
        return [
            'kode'  => ['required', 'string', 'max:40', Rule::unique('grounding_titiks', 'kode')->where('wilayah_id', $wilayahId)->ignore($ignoreId)],
            'nama'  => 'required|string|max:255',
            'peruntukan' => 'nullable|string|max:60',
            'pos_x' => 'nullable|numeric|between:0,100',
            'pos_y' => 'nullable|numeric|between:0,100',
            'latitude'  => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'tipe'  => 'nullable|string|max:40',
            'bahan' => 'nullable|string|max:60',
            'kedalaman_m' => 'nullable|numeric|min:0|max:1000',
            'diameter_mm' => 'nullable|numeric|min:0|max:10000',
            'panjang_kabel_m' => 'nullable|numeric|min:0|max:100000',
            'ukuran_kabel_mm2' => 'nullable|numeric|min:0|max:10000',
            'jumlah_elektroda' => 'nullable|integer|min:1|max:1000',
            'tahun_pasang' => 'nullable|integer|min:1950|max:'.(date('Y') + 1),
            'batas_ohm' => 'required|numeric|min:0.01|max:100000',
            'status' => 'required|in:aktif,rusak,dibongkar',
            'catatan' => 'nullable|string|max:2000',
            'fotos' => 'nullable|array|max:30',
            'fotos.*' => 'image|mimes:jpeg,png,jpg,webp|max:8192',
            'foto_keterangan' => 'nullable|array', 'foto_keterangan.*' => 'nullable|string|max:255',
            'foto_keterangan_baru' => 'nullable|array', 'foto_keterangan_baru.*' => 'nullable|string|max:255',
            'foto_keterangan_lama' => 'nullable|array', 'foto_keterangan_lama.*' => 'nullable|string|max:255',
            'hapus_foto' => 'nullable|array', 'hapus_foto.*' => 'integer',
        ];
    }

    private function data(array $v): array
    {
        return collect($v)->only(['kode','nama','peruntukan','pos_x','pos_y','latitude','longitude','tipe','bahan','kedalaman_m',
            'diameter_mm','panjang_kabel_m','ukuran_kabel_mm2','jumlah_elektroda','tahun_pasang','batas_ohm','status','catatan'])->all();
    }

    public function create(Request $request)
    {
        $this->kelola();
        $w = GroundingWilayah::findOrFail((int) $request->query('wilayah'));
        $w->load('titiks');
        $kodeUsul = 'GRD-'.strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $w->nama), 0, 3)).'-'.str_pad((string) ($w->titiks->count() + 1), 2, '0', STR_PAD_LEFT);
        return view('grounding.titik-form', ['t' => new GroundingTitik(['wilayah_id' => $w->id, 'kode' => $kodeUsul, 'batas_ohm' => 5, 'status' => 'aktif']), 'w' => $w]);
    }

    public function store(Request $request)
    {
        $this->kelola();
        $w = GroundingWilayah::findOrFail((int) $request->input('wilayah_id'));
        $this->normalisasiAngka($request, self::ANGKA);
        $v = $request->validate($this->rules($w->id));
        $t = GroundingTitik::create(['wilayah_id' => $w->id] + $this->data($v));
        $gagal = $this->simpanFotoRelasi($request, $t->fotos(), 'grounding/titik', 'foto_keterangan', 0);
        $r = redirect()->route('grounding.titik.show', $t)->with('success', 'Titik grounding disimpan.');
        return $gagal ? $r->with('warning', "$gagal foto gagal diunggah. Buka Edit untuk menambahkannya lagi.") : $r;
    }

    public function show(GroundingTitik $groundingTitik)
    {
        $this->masuk();
        $groundingTitik->load(['wilayah', 'fotos', 'ukurs.user', 'ukurs.fotos']);
        $evidenBoleh = \App\Models\Eviden::dapatDiakses(auth()->user())
            ->whereIn('id', $groundingTitik->ukurs->pluck('eviden_id')->filter()->all())->pluck('id')->all();
        $seri = $groundingTitik->ukurs->sortBy(fn($u) => $u->tanggal->format('Y-m-d').sprintf('%08d', $u->id))->values();
        $jadwal = null; $jadwalHasil = null;
        try {
            if (\Illuminate\Support\Facades\Schema::hasColumn('perawatan_rutin', 'grounding_titik_id')) {
                $jadwal = PerawatanRutin::where('objek_tipe', 'grounding')->where('grounding_titik_id', $groundingTitik->id)->where('aktif', true)->first();
                $jadwalHasil = $jadwal ? $jadwal->hitung() : null;
            }
        } catch (\Throwable $e) { $jadwal = null; }
        return view('grounding.titik-show', [
            't' => $groundingTitik, 'seri' => $seri, 'jadwal' => $jadwal, 'jadwalHasil' => $jadwalHasil,
            'bisaKelola' => Akses::groundingKelola(auth()->user()), 'evidenBoleh' => $evidenBoleh,
        ]);
    }

    public function edit(GroundingTitik $groundingTitik)
    {
        $this->kelola();
        $groundingTitik->load(['wilayah', 'fotos']);
        return view('grounding.titik-form', ['t' => $groundingTitik, 'w' => $groundingTitik->wilayah]);
    }

    public function update(Request $request, GroundingTitik $groundingTitik)
    {
        $this->kelola();
        $this->normalisasiAngka($request, self::ANGKA);
        $v = $request->validate($this->rules($groundingTitik->wilayah_id, $groundingTitik->id));
        $groundingTitik->update($this->data($v));

        foreach ((array) $request->input('foto_keterangan_lama', []) as $id => $ket) {
            $groundingTitik->fotos()->where('id', (int) $id)->update(['keterangan' => $this->bersihKet($ket)]);
        }
        foreach ((array) $request->input('hapus_foto', []) as $id) {
            $f = $groundingTitik->fotos()->where('id', (int) $id)->first();
            if ($f) { ImageHelper::deleteWithThumbnail($f->path); $f->delete(); }
        }
        $gagal = $this->simpanFotoRelasi($request, $groundingTitik->fotos(), 'grounding/titik', 'foto_keterangan_baru',
            (int) ($groundingTitik->fotos()->max('urutan') ?? -1) + 1);

        $r = redirect()->route('grounding.titik.show', $groundingTitik)->with('success', 'Titik grounding diperbarui.');
        return $gagal ? $r->with('warning', "$gagal foto gagal diunggah.") : $r;
    }

    public function destroy(GroundingTitik $groundingTitik)
    {
        $this->kelola();
        $wid = $groundingTitik->wilayah_id;
        foreach ($groundingTitik->fotos as $f) ImageHelper::deleteWithThumbnail($f->path);
        foreach ($groundingTitik->ukurs()->with('fotos')->get() as $u) {
            foreach ($u->fotos as $f) ImageHelper::deleteWithThumbnail($f->path);
        }
        $groundingTitik->delete();
        return redirect()->route('grounding.wilayah.show', $wid)->with('success', 'Titik grounding dihapus beserta riwayat pengukurannya.');
    }
}
