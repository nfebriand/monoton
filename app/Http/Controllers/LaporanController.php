<?php
namespace App\Http\Controllers;

use App\Models\OperasionalLog;
use App\Models\Pemancar;
use App\Models\User;
use App\Models\Eviden;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LaporanController extends Controller
{
    private static function dompdfOptions(): \Dompdf\Options
    {
        $fontDir   = storage_path('app/dompdf/fonts');
        $fontCache = storage_path('app/dompdf/font-cache');
        $tempDir   = storage_path('app/dompdf/tmp');
        foreach ([$fontDir, $fontCache, $tempDir] as $dir) {
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
        }
        $options = new \Dompdf\Options();
        $options->setIsRemoteEnabled(true);
        $options->setIsHtml5ParserEnabled(true);
        $options->setDefaultFont('dejavu sans');
        $options->setFontDir($fontDir);
        $options->setFontCache($fontCache);
        $options->setTempDir($tempDir);
        $options->setChroot([base_path(), public_path(), storage_path()]);
        return $options;
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        // Pemancar & lokasi Transmisi
        $pemancarQuery = Pemancar::orderBy('lokasi')->orderBy('nama_stasiun');
        if ($user->isOperator() && $user->isDivisi('transmisi') && $user->lokasi_dinas) {
            $pemancarQuery->where('lokasi', $user->lokasi_dinas);
        }
        $pemancars       = $pemancarQuery->get();
        $lokasiTransmisi = Pemancar::whereNotNull('lokasi')->distinct()->orderBy('lokasi')->pluck('lokasi');

        // Lokasi eviden (dari tabel evidens)
        $lokasiEviden = \App\Models\Eviden::whereNotNull('lokasi')
            ->distinct()->orderBy('lokasi')->pluck('lokasi');

        // Operator per divisi — kirim sebagai array biasa agar aman di @json()
        $allUsers = User::whereIn('role', [User::ROLE_OPERATOR, User::ROLE_ADMIN_DIVISI])
            ->where('is_active', true)->orderBy('divisi')->orderBy('name')->get();

        $operatorPerDivisi = [
            'transmisi' => $allUsers->where('divisi','transmisi')->values()
                ->map(fn($u) => ['id'=>$u->id,'name'=>$u->name,'nip'=>$u->nip])->values()->toArray(),
            'studio'    => $allUsers->where('divisi','studio')->values()
                ->map(fn($u) => ['id'=>$u->id,'name'=>$u->name,'nip'=>$u->nip])->values()->toArray(),
            'sarana'    => $allUsers->where('divisi','sarana')->values()
                ->map(fn($u) => ['id'=>$u->id,'name'=>$u->name,'nip'=>$u->nip])->values()->toArray(),
        ];

        // Lokasi kerja per divisi
        $lokasiPerDivisi = [];
        foreach (['transmisi','studio','sarana'] as $d) {
            $lokasiPerDivisi[$d] = User::where('divisi',$d)
                ->whereNotNull('lokasi_dinas')->distinct()
                ->orderBy('lokasi_dinas')->pluck('lokasi_dinas')->toArray();
        }

        // Operator Collection untuk view (backward compat)
        $operators = $allUsers;

        return view('laporan.index', [
            'pemancars'          => $pemancars,
            'lokasiTransmisi'    => $lokasiTransmisi,
            'lokasiEviden'       => $lokasiEviden,
            'operatorPerDivisi'  => $operatorPerDivisi,
            'lokasiPerDivisi'    => $lokasiPerDivisi,
            'operators'          => $operators,
            // backward compat
            'lokasiList'         => $lokasiTransmisi,
            'semuaLokasi'        => $lokasiTransmisi,
            'semuaPemancar'      => $pemancars,
            'semuaOperator'      => $operators,
        ]);
    }

    /** ── Laporan Operasional PDF ── */
    public function generate(Request $request)
    {
        try {
            $tanggal_dari = $request->input('tanggal_dari') ?? $request->input('tgl_dari')
                ?? $request->input('dari') ?? now()->startOfMonth()->toDateString();
            $tanggal_sampai = $request->input('tanggal_sampai') ?? $request->input('tgl_sampai')
                ?? $request->input('sampai') ?? now()->toDateString();

            try {
                $tanggal_dari   = Carbon::parse($tanggal_dari)->toDateString();
                $tanggal_sampai = Carbon::parse($tanggal_sampai)->toDateString();
            } catch (\Throwable $e) {
                $tanggal_dari   = now()->startOfMonth()->toDateString();
                $tanggal_sampai = now()->toDateString();
            }

            $user = auth()->user();
            $query = OperasionalLog::with(['pemancar','user','jadwalShift'])
                ->whereDate('dicatat_pada', '>=', $tanggal_dari)
                ->whereDate('dicatat_pada', '<=', $tanggal_sampai)
                ->orderBy('dicatat_pada');

            if ($user->isOperator() && $user->lokasi_dinas) {
                $query->whereHas('pemancar', fn($q) => $q->where('lokasi', $user->lokasi_dinas));
            }

            $pemancarFilter = null;
            if ($request->filled('pemancar_id')) {
                $pemancarFilter = Pemancar::find($request->pemancar_id);
                $query->where('pemancar_id', $request->pemancar_id);
            }

            $lokasiFilter = $request->lokasi ?: ($user->isOperator() ? $user->lokasi_dinas : null);
            if ($request->filled('lokasi')) {
                $query->whereHas('pemancar', fn($q) => $q->where('lokasi', $request->lokasi));
            }

            $operatorFilter = null;
            if ($request->filled('user_id')) {
                $operatorFilter = User::find($request->user_id);
                $query->where('user_id', $request->user_id);
            }

            $logs    = $query->get();
            $summary = [
                'pemancar_filter'  => $pemancarFilter,
                'lokasi_filter'    => $lokasiFilter,
                'operator_filter'  => $operatorFilter,
                'total_pencatatan' => $logs->count(),
                'rata_output_final'=> $logs->whereNotNull('output_final_pa')->avg('output_final_pa'),
                'rata_vswr_final'  => $logs->whereNotNull('vswr_final')->avg('vswr_final'),
                'rata_suhu_ruangan'=> $logs->whereNotNull('suhu_ruangan')->avg('suhu_ruangan'),
                'rata_kelembaban'  => $logs->whereNotNull('kelembaban')->avg('kelembaban'),
            ];

            $settings = AppSetting::allKeyed();
            $html = view('laporan.pdf', [
                'logs'           => $logs,
                'summary'        => $summary,
                'tanggal_dari'   => $tanggal_dari,
                'tanggal_sampai' => $tanggal_sampai,
                'satkerName'     => $settings['satuan_kerja'] ?? '',
                'pengelola'      => $settings['koordinator']  ?? '',
                'koordinator'    => AppSetting::getKoordinator('transmisi'),
                'generated_by'   => $user->name,
                'generated_at'   => now()->format('d/m/Y H:i'),
            ])->render();

            $dompdf = new \Dompdf\Dompdf(self::dompdfOptions());
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->render();

            return response($dompdf->output(), 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="laporan-operasional-'.$tanggal_dari.'-sd-'.$tanggal_sampai.'.pdf"',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Gagal generate laporan: '.$e->getMessage());
            return back()->withErrors(['pdf' => 'Gagal membuat PDF: '.$e->getMessage()])->withInput();
        }
    }

    /** ── Halaman Grafik Suhu ── */
    public function suhuBulanan(Request $request)
    {
        $user  = auth()->user();
        $bulan = (int)$request->get('bulan', now()->month);
        $tahun = (int)$request->get('tahun', now()->year);

        $pemancarQuery = Pemancar::orderBy('lokasi')->orderBy('nama_stasiun');
        if ($user->isOperator() && $user->lokasi_dinas) {
            $pemancarQuery->where('lokasi', $user->lokasi_dinas);
        }
        $pemancars  = $pemancarQuery->get();
        $lokasiList = Pemancar::whereNotNull('lokasi')->distinct()->orderBy('lokasi')->pluck('lokasi');

        $query = OperasionalLog::with('pemancar')
            ->whereYear('dicatat_pada', $tahun)
            ->whereMonth('dicatat_pada', $bulan)
            ->whereNotNull('suhu_ruangan')
            ->orderBy('dicatat_pada');

        if ($user->isOperator() && $user->lokasi_dinas) {
            $query->whereHas('pemancar', fn($q) => $q->where('lokasi', $user->lokasi_dinas));
        }
        if ($request->filled('pemancar_id')) $query->where('pemancar_id', $request->pemancar_id);
        if ($request->filled('lokasi')) $query->whereHas('pemancar', fn($q) => $q->where('lokasi', $request->lokasi));

        $logs = $query->get();

        $chartData = $logs->map(fn($l) => [
            'x'             => $l->dicatat_pada->format('d/m H:i'),
            'suhu_ruangan'  => $l->suhu_ruangan,
            'suhu_pemancar' => $l->suhu_pemancar,
            'kelembaban'    => $l->kelembaban,
        ]);

        $bulanPrev = $bulan == 1 ? 12 : $bulan - 1;
        $tahunPrev = $bulan == 1 ? $tahun - 1 : $tahun;
        $bulanNext = $bulan == 12 ? 1 : $bulan + 1;
        $tahunNext = $bulan == 12 ? $tahun + 1 : $tahun;
        $namaBulan = Carbon::create($tahun, $bulan, 1)->translatedFormat('F Y');

        return view('laporan.suhu', [
            'pemancars'    => $pemancars,
            'lokasiList'   => $lokasiList,
            'semuaLokasi'  => $lokasiList,
            'semuaPemancar'=> $pemancars,
            'pemancarId'   => $request->pemancar_id,
            'lokasiId'     => $request->lokasi,
            'lokasi'       => $request->lokasi,
            'logs'         => $logs,
            'dataGrafik'   => $logs,
            'chartData'    => $chartData,
            'bulan'        => $bulan,
            'tahun'        => $tahun,
            'bulanPrev'    => $bulanPrev,
            'tahunPrev'    => $tahunPrev,
            'bulanNext'    => $bulanNext,
            'tahunNext'    => $tahunNext,
            'namaBulan'    => $namaBulan,
        ]);
    }

    /** ── PDF Laporan Suhu ── */
    public function suhuPdf(Request $request)
    {
        try {
            $user  = auth()->user();
            $bulan = (int)$request->get('bulan', now()->month);
            $tahun = (int)$request->get('tahun', now()->year);

            $query = OperasionalLog::with(['pemancar','user','jadwalShift'])
                ->whereYear('dicatat_pada', $tahun)
                ->whereMonth('dicatat_pada', $bulan)
                ->orderBy('dicatat_pada');

            if ($user->isOperator() && $user->lokasi_dinas) {
                $query->whereHas('pemancar', fn($q) => $q->where('lokasi', $user->lokasi_dinas));
            }

            $pemancar = null;
            if ($request->filled('pemancar_id')) {
                $pemancar = Pemancar::find($request->pemancar_id);
                $query->where('pemancar_id', $request->pemancar_id);
            }

            $lokasi = $request->lokasi ?: ($user->isOperator() ? $user->lokasi_dinas : null);
            if ($request->filled('lokasi')) {
                $query->whereHas('pemancar', fn($q) => $q->where('lokasi', $request->lokasi));
            }

            $logs     = $query->get();
            $settings = AppSetting::allKeyed();

            $html = view('laporan.suhu-pdf', [
                'logs'            => $logs,
                'bulanLabel'      => Carbon::create($tahun, $bulan, 1)->translatedFormat('F Y'),
                'pemancar'        => $pemancar,
                'lokasi'          => $lokasi,
                'satkerName'      => $settings['satuan_kerja'] ?? '',
                'koordinatorName' => $settings['koordinator']  ?? '',
                'koordinator'     => AppSetting::getKoordinator('transmisi'),
                'operatorNama'    => $user->name,
                'operatorNip'     => $user->nip ?? null,
            ])->render();

            $dompdf = new \Dompdf\Dompdf(self::dompdfOptions());
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->render();

            return response($dompdf->output(), 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="laporan-suhu-'.$tahun.'-'.str_pad($bulan,2,'0',STR_PAD_LEFT).'.pdf"',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Gagal generate suhu PDF: '.$e->getMessage());
            return back()->withErrors(['pdf' => 'Gagal membuat PDF: '.$e->getMessage()]);
        }
    }

    // ════════════════════════════════════════════
    //  B. LAPORAN EVIDEN PER DIVISI
    // ════════════════════════════════════════════

    /** Halaman rekap eviden (filter divisi, periode, lokasi) */
    public function evidenRekap(Request $request)
    {
        $user = auth()->user();

        $query = Eviden::with(['user','operators','fotos'])
            ->orderByDesc('tanggal');

        // Scoping divisi
        if ($user->isAdminDivisi()) {
            $query->where('divisi', $user->divisi);
        } elseif ($request->filled('divisi')) {
            $query->where('divisi', $request->divisi);
        }

        if ($request->filled('tanggal_dari'))   $query->where('tanggal', '>=', $request->tanggal_dari);
        if ($request->filled('tanggal_sampai')) $query->where('tanggal', '<=', $request->tanggal_sampai);
        if ($request->filled('lokasi'))         $query->where('lokasi', $request->lokasi);
        if ($request->filled('user_id'))        $query->where('user_id', $request->user_id);

        $evidens    = $query->paginate(15)->withQueryString();
        $lokasiList = Eviden::whereNotNull('lokasi')->distinct()->orderBy('lokasi')->pluck('lokasi');
        $operators  = User::where('role', User::ROLE_OPERATOR)
            ->when($user->isAdminDivisi(), fn($q) => $q->where('divisi', $user->divisi))
            ->orderBy('name')->get();

        // Ringkasan per divisi
        $ringkasan = [];
        foreach (['transmisi','studio','sarana'] as $d) {
            if ($user->isAdminDivisi() && $user->divisi !== $d) continue;
            $base = Eviden::where('divisi', $d);
            if ($request->filled('tanggal_dari'))   (clone $base)->where('tanggal','>=',$request->tanggal_dari);
            if ($request->filled('tanggal_sampai')) (clone $base)->where('tanggal','<=',$request->tanggal_sampai);
            $ringkasan[$d] = [
                'label'       => \App\Models\User::DIVISI_LABEL[$d],
                'total'       => Eviden::where('divisi',$d)->count(),
                'bulan_ini'   => Eviden::where('divisi',$d)->whereMonth('tanggal',now()->month)->whereYear('tanggal',now()->year)->count(),
            ];
        }

        return view('laporan.eviden-rekap', compact(
            'evidens','lokasiList','operators','ringkasan'
        ));
    }

    /** Cetak Rekap Eviden PDF */
    public function evidenRekapPdf(Request $request)
    {
        try {
            $user = auth()->user();

            $query = Eviden::with(['user','operators'])
                ->orderByDesc('tanggal');

            if ($user->isAdminDivisi()) {
                $query->where('divisi', $user->divisi);
            } elseif ($request->filled('divisi')) {
                $query->where('divisi', $request->divisi);
            }

            if ($request->filled('tanggal_dari'))   $query->where('tanggal', '>=', $request->tanggal_dari);
            if ($request->filled('tanggal_sampai')) $query->where('tanggal', '<=', $request->tanggal_sampai);
            if ($request->filled('lokasi'))         $query->where('lokasi', $request->lokasi);

            $evidens  = $query->get();
            $divisi   = $request->divisi ?: ($user->isAdminDivisi() ? $user->divisi : 'semua');
            $settings = AppSetting::allKeyed();

            $koordinator = $divisi === 'semua'
                ? AppSetting::getKoordinator('transmisi')
                : AppSetting::getKoordinator($divisi);

            $html = view('laporan.eviden-rekap-pdf', [
                'evidens'        => $evidens,
                'divisi'         => $divisi,
                'divisiLabel'    => \App\Models\User::DIVISI_LABEL[$divisi] ?? 'Semua Divisi',
                'tanggal_dari'   => $request->tanggal_dari ?? null,
                'tanggal_sampai' => $request->tanggal_sampai ?? null,
                'satkerName'     => $settings['satuan_kerja'] ?? '',
                'koordinator'    => $koordinator,
                'kabid'          => AppSetting::getKabid(),
                'generated_by'   => $user->name,
                'generated_at'   => now()->format('d/m/Y H:i'),
            ])->render();

            $dompdf = new \Dompdf\Dompdf(self::dompdfOptions());
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->render();

            $filename = 'rekap-eviden-'.$divisi.'-'.now()->format('Ymd').'.pdf';
            return response($dompdf->output(), 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Gagal generate rekap eviden PDF: '.$e->getMessage());
            return back()->withErrors(['pdf' => 'Gagal membuat PDF: '.$e->getMessage()]);
        }
    }
}