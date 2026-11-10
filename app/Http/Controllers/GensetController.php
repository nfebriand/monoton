<?php
namespace App\Http\Controllers;

use App\Models\GensetLog;
use App\Models\GensetUnit;
use App\Models\Lokasi;
use App\Support\Akses;
use Illuminate\Http\Request;

class GensetController extends Controller
{
    public const PESAN = [
        'tanggal.before_or_equal' => 'Tanggal tidak boleh di masa depan.',
    ];

    /** Kewajaran angka satu log genset (kini hanya pengisian BBM). Mengembalikan pesan kesalahan atau null. */
    public static function cekRentang(array $v, $unit): ?string
    {
        $isi = (isset($v['bbm_isi']) && $v['bbm_isi'] !== '' && is_numeric($v['bbm_isi'])) ? (float) $v['bbm_isi'] : null;
        $tangki = $unit && $unit->kapasitas_tangki_liter ? (float) $unit->kapasitas_tangki_liter : null;
        if ($isi !== null && $tangki !== null && $isi > $tangki + 0.5) {
            return "Pengisian BBM ($isi L) melebihi kapasitas tangki unit ($tangki L).";
        }
        return null;
    }

    /** Pastikan hanya divisi Sarana / Admin yang akses */
    private function checkAccess(): void
    {
        // Aturan: App\Support\Akses::gensetMasuk (super admin, Sarana, Transmisi)
        if (!Akses::gensetMasuk(auth()->user())) {
            abort(403, 'Menu ini hanya untuk Divisi Sarana & Prasarana atau Transmisi.');
        }
    }


    /**
     * Cek akses manajemen unit genset.
     * Hanya Super Admin dan Admin Divisi Sarana yang diperbolehkan
     * (bukan Admin Divisi divisi lain — genset adalah aset Sarana & Prasarana).
     */
    private function checkUnitAccess(): void
    {
        if (!Akses::gensetUnitKelola(auth()->user())) {
            abort(403, 'Anda tidak memiliki akses untuk mengelola unit genset. Menu ini khusus Super Admin dan Admin Divisi Sarana & Prasarana.');
        }
    }

    public function index(Request $request)
    {
        $this->checkAccess();

        $query = GensetLog::with(['gensetUnit','user'])
            ->orderByDesc('tanggal')->orderByDesc('jam_mulai');

        if ($request->filled('genset_unit_id')) $query->where('genset_unit_id',$request->genset_unit_id);
        if ($request->filled('tanggal_dari'))   $query->where('tanggal','>=',$request->tanggal_dari);
        if ($request->filled('tanggal_sampai')) $query->where('tanggal','<=',$request->tanggal_sampai);
        if ($request->filled('kondisi'))        $query->where('kondisi',$request->kondisi);

        $logs  = $query->paginate(15)->withQueryString();
        $units = GensetUnit::where('is_active',true)->orderBy('nama_unit')->get();

        // Ringkasan bulan ini
        $bulanIni = GensetLog::whereMonth('tanggal',now()->month)->whereYear('tanggal',now()->year);
        $ringkasan = [
            'total_operasi'   => (clone $bulanIni)->count(),
            'total_bbm'       => (float) (clone $bulanIni)->sum('bbm_isi'),
            'total_jam'       => (clone $bulanIni)->get()->sum(fn($l)=>$l->jam_operasi_hm ?? $l->durasi_jam ?? 0),
            'gangguan'        => (clone $bulanIni)->where('kondisi','gangguan')->count(),
        ];

        return view('genset.index', compact('logs','units','ringkasan'));
    }

    public function create()
    {
        $this->checkAccess();
        $units = GensetUnit::where('is_active',true)->orderBy('nama_unit')->get();

        if ($units->isEmpty()) {
            return redirect()->route('genset.index')
                ->with('error','Belum ada unit genset terdaftar. Hubungi admin untuk menambahkan unit genset terlebih dahulu.');
        }

        return view('genset.create', compact('units'));
    }

    public function store(Request $request)
    {
        $this->checkAccess();

        $v = $request->validate([
            'genset_unit_id' => 'required|exists:genset_units,id',
            'tanggal'        => 'required|date|before_or_equal:today',
            'jam_mulai'      => 'required|date_format:H:i',
            'jam_selesai'    => 'nullable|date_format:H:i',
            'alasan'         => 'required|in:pln_mati,maintenance,test_rutin,lainnya',
            'bbm_isi'        => 'nullable|numeric|min:0|max:100000',
            'kondisi'        => 'required|in:normal,gangguan',
            'keterangan'     => 'nullable|string',
        ], self::PESAN);

        if ($err = self::cekRentang($v, GensetUnit::find($v['genset_unit_id']))) {
            return back()->withErrors(['rentang' => $err])->withInput();
        }

        $v['user_id'] = auth()->id();
        GensetLog::create($v);

        return redirect()->route('genset.index')->with('success','Log operasional genset berhasil disimpan.');
    }

    /** Data rekap pengisian BBM (dipakai halaman & PDF). */
    private function dataRekapBbm(Request $request): array
    {
        $v = $request->validate([
            'dari' => 'nullable|date', 'sampai' => 'nullable|date|after_or_equal:dari',
            'genset_unit_id' => 'nullable|integer', 'lokasi' => 'nullable|string|max:100',
        ]);
        $dari   = $v['dari']   ?? now()->startOfMonth()->toDateString();
        $sampai = $v['sampai'] ?? now()->toDateString();
        $q = GensetLog::with(['gensetUnit', 'user'])->where('bbm_isi', '>', 0)->whereBetween('tanggal', [$dari, $sampai]);
        if (!empty($v['genset_unit_id'])) $q->where('genset_unit_id', $v['genset_unit_id']);
        if (!empty($v['lokasi'])) $q->whereHas('gensetUnit', fn($u) => $u->where('lokasi', $v['lokasi']));
        $logs = $q->orderBy('tanggal')->orderBy('jam_mulai')->limit(2000)->get();

        $perUnit = $logs->groupBy('genset_unit_id')->map(function ($g) {
            $u = $g->first()->gensetUnit;
            return ['unit' => $u, 'jumlah' => $g->count(), 'total' => (float) $g->sum('bbm_isi'),
                'rata' => round((float) $g->avg('bbm_isi'), 1), 'terakhir' => $g->max('tanggal')];
        })->sortBy(fn($r) => $r['unit']->nama_unit ?? '')->values();

        $perBulan = $logs->groupBy(fn($l) => $l->tanggal->format('Y-m'))->map(fn($g) => (float) $g->sum('bbm_isi'))->sortKeys();
        return [
            'dari' => $dari, 'sampai' => $sampai, 'logs' => $logs, 'perUnit' => $perUnit, 'perBulan' => $perBulan,
            'total' => (float) $logs->sum('bbm_isi'), 'filterUnit' => $v['genset_unit_id'] ?? null, 'filterLokasi' => $v['lokasi'] ?? null,
        ];
    }

    public function rekapBbm(Request $request)
    {
        $this->checkAccess();
        $d = $this->dataRekapBbm($request);
        $d['units'] = GensetUnit::orderBy('nama_unit')->get();
        $d['lokasis'] = GensetUnit::whereNotNull('lokasi')->distinct()->orderBy('lokasi')->pluck('lokasi');
        return view('genset.rekap-bbm', $d);
    }

    public function rekapBbmPdf(Request $request)
    {
        $this->checkAccess();
        $d = $this->dataRekapBbm($request);
        $d['settings'] = \App\Models\AppSetting::allKeyed();
        $d['pengelola'] = \App\Models\AppSetting::getKoordinator('sarana', \App\Models\AppSetting::tanggalAcuan($d['sampai']));
        $d['pembuat'] = auth()->user();
        $html = view('genset.pdf-rekap-bbm', $d)->render();

        @ini_set('memory_limit', '512M'); @set_time_limit(180);
        $dirs = [storage_path('app/dompdf/fonts'), storage_path('app/dompdf/font-cache'), storage_path('app/dompdf/tmp')];
        foreach ($dirs as $dir) { if (!is_dir($dir)) @mkdir($dir, 0775, true); }
        $opt = new \Dompdf\Options();
        $opt->setIsRemoteEnabled(false); $opt->setIsHtml5ParserEnabled(true); $opt->setDefaultFont('dejavu sans');
        $opt->setFontDir($dirs[0]); $opt->setFontCache($dirs[1]); $opt->setTempDir($dirs[2]);
        $opt->setChroot([base_path(), public_path(), storage_path()]);
        try {
            $pdf = new \Dompdf\Dompdf($opt);
            $pdf->setPaper('A4', 'portrait');
            $pdf->loadHtml($html, 'UTF-8');
            $pdf->render();
            return response($pdf->output(), 200, ['Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="rekap-bbm-genset-'.$d['dari'].'_'.$d['sampai'].'.pdf"']);
        } catch (\Throwable $e) {
            \Log::error('Gagal cetak rekap BBM genset: '.$e->getMessage());
            return back()->withErrors(['pdf' => 'Gagal membuat PDF: '.$e->getMessage()]);
        }
    }

    public function show(GensetLog $genset)
    {
        $this->checkAccess();
        $genset->load(['gensetUnit','user']);
        return view('genset.show', compact('genset'));
    }

    public function edit(GensetLog $genset)
    {
        $this->checkAccess();
        if (!Akses::gensetLogUbah(auth()->user(), $genset->user_id)) abort(403);
        $units = GensetUnit::where('is_active',true)->orderBy('nama_unit')->get();
        return view('genset.edit', compact('genset','units'));
    }

    public function update(Request $request, GensetLog $genset)
    {
        $this->checkAccess();
        if (!Akses::gensetLogUbah(auth()->user(), $genset->user_id)) abort(403);

        $v = $request->validate([
            'genset_unit_id' => 'required|exists:genset_units,id',
            'tanggal'        => 'required|date|before_or_equal:today',
            'jam_mulai'      => 'required|date_format:H:i',
            'jam_selesai'    => 'nullable|date_format:H:i',
            'alasan'         => 'required|in:pln_mati,maintenance,test_rutin,lainnya',
            'bbm_isi'        => 'nullable|numeric|min:0|max:100000',
            'kondisi'        => 'required|in:normal,gangguan',
            'keterangan'     => 'nullable|string',
        ], self::PESAN);

        if ($err = self::cekRentang($v, GensetUnit::find($v['genset_unit_id']))) {
            return back()->withErrors(['rentang' => $err])->withInput();
        }

        $genset->update($v);
        return redirect()->route('genset.show',$genset)->with('success','Log genset berhasil diperbarui.');
    }

    public function destroy(GensetLog $genset)
    {
        $this->checkAccess();
        $this->checkUnitAccess();
        $genset->delete();
        return redirect()->route('genset.index')->with('success','Log genset berhasil dihapus.');
    }

    // ════════════════════════════════════════════
    //  MANAJEMEN UNIT GENSET (Admin Only)
    // ════════════════════════════════════════════

    public function units()
    {
        $this->checkUnitAccess();
        $units   = GensetUnit::orderBy('lokasi')->orderBy('nama_unit')->get();
        $lokasis = Lokasi::orderBy('nama')->get();
        return view('genset.units', compact('units', 'lokasis'));
    }

    public function storeUnit(Request $request)
    {
        $this->checkUnitAccess();
        $v = $request->validate([
            'nama_unit'             => 'required|string|max:100',
            'lokasi'                => 'nullable|string|max:100',
            'merk'                  => 'nullable|string|max:100',
            'tipe'                  => 'nullable|string|max:100',
            'kapasitas_kva'         => 'nullable|integer|min:0',
            'kapasitas_tangki_liter'=> 'nullable|numeric|min:0',
            'tahun_pembuatan'       => 'nullable|integer',
            'is_active'             => 'nullable|boolean',
        ]);
        $v['is_active'] = $request->boolean('is_active', true);
        GensetUnit::create($v);
        return redirect()->route('genset.units')->with('success','Unit genset berhasil ditambahkan.');
    }

    public function updateUnit(Request $request, GensetUnit $unit)
    {
        $this->checkUnitAccess();
        $v = $request->validate([
            'nama_unit'             => 'required|string|max:100',
            'lokasi'                => 'nullable|string|max:100',
            'merk'                  => 'nullable|string|max:100',
            'tipe'                  => 'nullable|string|max:100',
            'kapasitas_kva'         => 'nullable|integer|min:0',
            'kapasitas_tangki_liter'=> 'nullable|numeric|min:0',
            'tahun_pembuatan'       => 'nullable|integer',
            'is_active'             => 'nullable|boolean',
        ]);
        $v['is_active'] = $request->boolean('is_active', true);
        $unit->update($v);
        return redirect()->route('genset.units')->with('success','Unit genset berhasil diperbarui.');
    }

    public function destroyUnit(GensetUnit $unit)
    {
        $this->checkUnitAccess();
        if ($unit->logs()->exists()) {
            return back()->withErrors(['unit'=>'Unit ini memiliki riwayat log dan tidak bisa dihapus. Nonaktifkan saja.']);
        }
        $unit->delete();
        return redirect()->route('genset.units')->with('success','Unit genset berhasil dihapus.');
    }
}
