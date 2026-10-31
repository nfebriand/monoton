<?php

namespace App\Http\Controllers;

use App\Models\Eviden;
use App\Models\GensetUnit;
use App\Models\GroundingTitik;
use App\Models\Pemancar;
use App\Models\PerawatanRutin;
use App\Models\PerawatanRutinLog;
use App\Support\Akses;
use App\Support\PerawatanHitung;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Jadwal perawatan rutin pemancar & genset, berbasis tanggal dan/atau jam kerja (HM).
 * Aturan akses: App\Support\Akses::perawatan*.
 */
class PerawatanRutinController extends Controller
{
    private function wajibMenu(): void
    {
        abort_unless(Akses::perawatanMenu(auth()->user()), 403, 'Menu ini hanya untuk Divisi Transmisi atau Sarana & Prasarana.');
    }

    private function wajibLihat(PerawatanRutin $p): void
    {
        $lokasi = $p->objek_tipe === 'pemancar' ? ($p->pemancar->lokasi ?? null) : null;
        abort_unless(Akses::perawatanLihat(auth()->user(), $p->objek_tipe, $lokasi), 403, 'Anda tidak berhak melihat jadwal ini.');
    }

    private function wajibKelola(string $tipe): void
    {
        abort_unless(Akses::perawatanKelola(auth()->user(), $tipe), 403,
            in_array($tipe, ['genset', 'grounding'], true)
                ? 'Jadwal perawatan genset/grounding hanya dapat dikelola Super Admin / Admin Divisi Sarana & Prasarana.'
                : 'Jadwal perawatan pemancar hanya dapat dikelola Super Admin / Admin Divisi Transmisi.');
    }

    public function index(Request $request)
    {
        $this->wajibMenu();
        $user = auth()->user();

        $query = PerawatanRutin::with(['pemancar', 'gensetUnit', 'groundingTitik.wilayah'])->bolehDilihat($user)->orderBy('objek_tipe')->orderBy('nama_tugas');
        if (in_array($request->tipe, ['pemancar', 'genset', 'grounding'], true)) $query->where('objek_tipe', $request->tipe);
        if (!$request->boolean('semua')) $query->where('aktif', true);

        $map   = PerawatanRutin::hmMap();
        $baris = $query->get()->map(fn($i) => ['item' => $i, 'hasil' => $i->hitung($map)]);

        $hitung = ['terlambat' => 0, 'segera' => 0, 'belum' => 0, 'ok' => 0];
        foreach ($baris as $b) $hitung[$b['hasil']['status']]++;

        if (in_array($request->status, array_keys($hitung), true)) {
            $baris = $baris->filter(fn($b) => $b['hasil']['status'] === $request->status);
        }
        // Terparah di atas
        $baris = $baris->sortByDesc(fn($b) => PerawatanHitung::RANK[$b['hasil']['status']])->values();

        $bisaKelolaPemancar = Akses::perawatanKelola($user, 'pemancar');
        $bisaKelolaGenset   = Akses::perawatanKelola($user, 'genset');

        $lihatPemancar = Akses::menuTransmisi($user);
        $lihatGenset   = Akses::gensetMasuk($user);
        $lihatGrounding = Akses::menuSarana($user);

        return view('perawatan.index', compact('baris', 'hitung', 'bisaKelolaPemancar', 'bisaKelolaGenset', 'lihatPemancar', 'lihatGenset', 'lihatGrounding'));
    }

    private function dataForm(): array
    {
        $user = auth()->user();
        return [
            'pemancars' => Akses::perawatanKelola($user, 'pemancar') ? Pemancar::where('is_active', true)->orderBy('lokasi')->orderBy('nama_stasiun')->get() : collect(),
            'units'     => Akses::perawatanKelola($user, 'genset')   ? GensetUnit::where('is_active', true)->orderBy('nama_unit')->get() : collect(),
            'titiks'    => Akses::perawatanKelola($user, 'grounding') ? GroundingTitik::with('wilayah')->where('status', 'aktif')->orderBy('wilayah_id')->orderBy('kode')->get() : collect(),
            'preset'    => PerawatanRutin::PRESET,
            'hmMap'     => PerawatanRutin::hmMap(),
        ];
    }

    public function create(Request $request)
    {
        $this->wajibMenu();
        $user = auth()->user();
        abort_unless(Akses::perawatanKelola($user, 'pemancar') || Akses::perawatanKelola($user, 'genset') || Akses::perawatanKelola($user, 'grounding'), 403, 'Anda tidak berhak mengelola jadwal perawatan.');
        // Objek bawaan menyesuaikan hak pengguna: user Sarana & Prasarana -> genset, user Transmisi -> pemancar
        $boleh = array_values(array_filter(['pemancar', 'genset', 'grounding'], fn($t) => Akses::perawatanKelola($user, $t)));
        $tipe  = in_array($request->get('tipe'), $boleh, true) ? $request->get('tipe') : ($boleh[0] ?? 'pemancar');
        return view('perawatan.form', array_merge($this->dataForm(), ['item' => new PerawatanRutin(['objek_tipe' => $tipe, 'basis' => 'tanggal', 'aktif' => true])]));
    }

    private function validasi(Request $request): array
    {
        $v = $request->validate([
            'objek_tipe'       => 'required|in:pemancar,genset,grounding',
            'grounding_titik_id' => 'required_if:objek_tipe,grounding|nullable|exists:grounding_titiks,id',
            'pemancar_id'      => 'required_if:objek_tipe,pemancar|nullable|exists:pemancars,id',
            'genset_unit_id'   => 'required_if:objek_tipe,genset|nullable|exists:genset_units,id',
            'nama_tugas'       => 'required|string|max:150',
            'basis'            => 'required|in:tanggal,hm,keduanya',
            'interval_hari'    => 'required_if:basis,tanggal,keduanya|nullable|integer|min:1|max:3650',
            'interval_hm'      => 'required_if:basis,hm,keduanya|nullable|numeric|min:1|max:100000',
            'terakhir_tanggal' => 'nullable|date|before_or_equal:today',
            'terakhir_hm'      => 'nullable|numeric|min:0',
            'catatan'          => 'nullable|string|max:1000',
        ], [
            'interval_hari.required_if' => 'Isi interval hari untuk perawatan berbasis tanggal.',
            'interval_hm.required_if'   => 'Isi interval HM untuk perawatan berbasis jam kerja.',
            'terakhir_tanggal.before_or_equal' => 'Tanggal terakhir tidak boleh di masa depan.',
        ]);
        if (in_array($v['objek_tipe'], ['pemancar', 'grounding'], true) && $v['basis'] !== 'tanggal') {
            throw \Illuminate\Validation\ValidationException::withMessages(['basis' => 'Pemancar dan grounding tidak punya hour meter - gunakan basis tanggal.']);
        }
        $v['pemancar_id']       = $v['objek_tipe'] === 'pemancar'  ? ($v['pemancar_id'] ?? null) : null;
        $v['genset_unit_id']    = $v['objek_tipe'] === 'genset'    ? ($v['genset_unit_id'] ?? null) : null;
        $v['grounding_titik_id'] = $v['objek_tipe'] === 'grounding' ? ($v['grounding_titik_id'] ?? null) : null;
        if ($v['basis'] === 'tanggal') $v['interval_hm'] = null;
        if ($v['basis'] === 'hm') $v['interval_hari'] = null;
        $v['aktif'] = $request->boolean('aktif', true);
        return $v;
    }

    public function store(Request $request)
    {
        $this->wajibMenu();
        $v = $this->validasi($request);
        $this->wajibKelola($v['objek_tipe']);
        $v['dibuat_oleh'] = auth()->id();
        $item = PerawatanRutin::create($v);
        return redirect()->route('perawatan.show', $item)->with('success', 'Jadwal perawatan berhasil ditambahkan.');
    }

    public function show(PerawatanRutin $perawatanRutin)
    {
        $this->wajibMenu();
        $this->wajibLihat($perawatanRutin);
        $perawatanRutin->load(['pemancar', 'gensetUnit', 'groundingTitik.wilayah', 'logs.user', 'logs.eviden']);
        $map   = PerawatanRutin::hmMap();
        $hasil = $perawatanRutin->hitung($map);
        $hmSekarang = $perawatanRutin->hmSekarang($map);

        $user = auth()->user();
        // Eviden riwayat yang boleh dibuka pengguna ini (selain itu hanya tampil tanpa tautan)
        $evidenBoleh = Eviden::dapatDiakses($user)
            ->whereIn('id', $perawatanRutin->logs->pluck('eviden_id')->filter()->all())->pluck('id')->all();

        // Kandidat eviden untuk dikaitkan: milik/terlibat, 120 hari terakhir, belum dipakai jadwal ini
        $sudahDipakai = $perawatanRutin->logs->pluck('eviden_id')->filter()->all();
        $kandidat = Eviden::dapatDiakses($user)
            ->where('tanggal', '>=', now()->subDays(120)->toDateString())
            ->whereNotIn('id', $sudahDipakai)
            ->orderByDesc('tanggal')->orderByDesc('jam_mulai')->limit(50)->get(['id', 'judul', 'tanggal', 'lokasi']);

        // Datang dari "Buat Eviden Baru": eviden itu dipilih otomatis
        $evidenDipilih = null;
        if (request()->filled('eviden')) {
            $evidenDipilih = Eviden::dapatDiakses($user)->whereNotIn('id', $sudahDipakai)->find((int) request('eviden'));
            if ($evidenDipilih && !$kandidat->contains('id', $evidenDipilih->id)) $kandidat->prepend($evidenDipilih);
        }

        return view('perawatan.show', [
            'item' => $perawatanRutin, 'hasil' => $hasil, 'hmSekarang' => $hmSekarang,
            'evidenBoleh' => $evidenBoleh, 'kandidat' => $kandidat, 'evidenDipilih' => $evidenDipilih,
        ]);
    }

    public function edit(PerawatanRutin $perawatanRutin)
    {
        $this->wajibMenu();
        $this->wajibKelola($perawatanRutin->objek_tipe);
        return view('perawatan.form', array_merge($this->dataForm(), ['item' => $perawatanRutin]));
    }

    public function update(Request $request, PerawatanRutin $perawatanRutin)
    {
        $this->wajibMenu();
        $this->wajibKelola($perawatanRutin->objek_tipe);
        $v = $this->validasi($request);
        $this->wajibKelola($v['objek_tipe']);
        $perawatanRutin->update($v);
        return redirect()->route('perawatan.show', $perawatanRutin)->with('success', 'Jadwal perawatan diperbarui.');
    }

    public function destroy(PerawatanRutin $perawatanRutin)
    {
        $this->wajibMenu();
        $this->wajibKelola($perawatanRutin->objek_tipe);
        DB::transaction(function () use ($perawatanRutin) {
            PerawatanRutinLog::where('perawatan_rutin_id', $perawatanRutin->id)->delete();
            $perawatanRutin->delete();
        });
        return redirect()->route('perawatan.index')->with('success', 'Jadwal perawatan dihapus.');
    }

    /** Catat pelaksanaan (operator yang bertugas pun boleh). Memperbarui acuan terakhir. */
    public function selesai(Request $request, PerawatanRutin $perawatanRutin)
    {
        $this->wajibMenu();
        $this->wajibLihat($perawatanRutin);

        $v = $request->validate([
            'tanggal' => 'required|date|before_or_equal:today',
            'hm'      => ($perawatanRutin->basis === 'tanggal' ? 'nullable' : 'required').'|numeric|min:0',
            'catatan' => 'nullable|string|max:1000',
            'eviden_id' => 'nullable|integer|exists:evidens,id',
        ], [
            'hm.required' => 'Isi HM saat perawatan dilakukan (acuan jadwal berikutnya).',
            'tanggal.before_or_equal' => 'Tanggal pelaksanaan tidak boleh di masa depan.',
        ]);

        // Eviden hanya boleh dikaitkan bila pengguna berhak melihatnya
        if (!empty($v['eviden_id']) && !Eviden::dapatDiakses(auth()->user())->whereKey($v['eviden_id'])->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['eviden_id' => 'Eviden tersebut tidak dapat Anda akses.']);
        }

        DB::transaction(function () use ($perawatanRutin, $v) {
            PerawatanRutinLog::create([
                'perawatan_rutin_id' => $perawatanRutin->id,
                'user_id'            => auth()->id(),
                'tanggal'            => $v['tanggal'],
                'hm'                 => $v['hm'] ?? null,
                'catatan'            => $v['catatan'] ?? null,
                'eviden_id'          => $v['eviden_id'] ?? null,
            ]);
            // Acuan hanya maju (tidak mundur bila mencatat pelaksanaan lama)
            $lebihBaru = !$perawatanRutin->terakhir_tanggal || $v['tanggal'] >= $perawatanRutin->terakhir_tanggal->format('Y-m-d');
            if ($lebihBaru) {
                $perawatanRutin->terakhir_tanggal = $v['tanggal'];
                if (isset($v['hm'])) $perawatanRutin->terakhir_hm = $v['hm'];
                $perawatanRutin->save();
            }
        });

        return redirect()->route('perawatan.show', $perawatanRutin)->with('success', 'Pelaksanaan perawatan dicatat. Jadwal berikutnya dihitung dari tanggal/HM ini.');
    }
}
