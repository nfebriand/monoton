<?php
namespace App\Http\Controllers;

use App\Models\Pemancar;
use App\Models\OperasionalLog;
use App\Models\Eviden;
use App\Models\GensetLog;
use App\Models\JadwalShift;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user   = auth()->user();
        $divisi = $user->divisi ?? 'transmisi';

        // Router: Sarana & Studio tampil dashboard yang berbeda
        if ($divisi === 'sarana') {
            return $this->dashboardSarana($request, $user);
        }
        if ($divisi === 'studio') {
            return $this->dashboardStudio($request, $user);
        }

        // Default: Transmisi (atau Admin melihat semua)
        return $this->dashboardTransmisi($request, $user);
    }

    // ── Dashboard Transmisi (existing) ──
    private function dashboardTransmisi(Request $request, $user)
    {
        $lokasiFilter = null;
        if ($user->isOperator() && $user->lokasi_dinas) {
            $lokasiFilter = $user->lokasi_dinas;
        } elseif ($user->hasAdminAccess() && $request->filled('lokasi')) {
            $lokasiFilter = $request->lokasi;
        }

        $pemancarQuery = Pemancar::with('fotos')->where('is_active', true);
        if ($lokasiFilter) $pemancarQuery->where('lokasi', $lokasiFilter);
        $pemancars = $pemancarQuery->orderBy('lokasi')->orderBy('nama_stasiun')->get();

        $pemancarIds = $pemancars->pluck('id');
        $logsTerakhir = OperasionalLog::with('user','jadwalShift')
            ->whereIn('pemancar_id', $pemancarIds)
            ->orderByDesc('dicatat_pada')
            ->get()
            ->groupBy('pemancar_id')
            ->map(fn($logs) => $logs->first());

        $pemancars->each(fn($p) => $p->logTerakhir = $logsTerakhir->get($p->id));

        $lokasiList = Pemancar::whereNotNull('lokasi')->distinct()->orderBy('lokasi')->pluck('lokasi');

        $statQuery = OperasionalLog::query();
        if ($lokasiFilter) {
            $statQuery->whereHas('pemancar', fn($q) => $q->where('lokasi', $lokasiFilter));
        }

        $totalLogHariIni    = (clone $statQuery)->whereDate('dicatat_pada', today())->count();
        $totalLogBulanIni   = (clone $statQuery)->whereMonth('dicatat_pada', now()->month)->whereYear('dicatat_pada', now()->year)->count();
        $totalEvidenBulanIni= Eviden::where('divisi','transmisi')->whereMonth('tanggal', now()->month)->whereYear('tanggal', now()->year)->count();

        $vswrBermasalah = (clone $statQuery)
            ->whereDate('dicatat_pada', today())
            ->where('vswr_final', '>=', 2)
            ->with('pemancar','user')
            ->orderByDesc('dicatat_pada')->limit(5)->get();

        $peringatanPencatatan = false;
        if ($user->isOperator() && $lokasiFilter) {
            $logTerbaruLokasi = OperasionalLog::whereHas('pemancar', fn($q) => $q->where('lokasi', $lokasiFilter))
                ->orderByDesc('dicatat_pada')->first();
            if (!$logTerbaruLokasi || $logTerbaruLokasi->dicatat_pada->diffInHours(now()) >= 3) {
                $peringatanPencatatan = true;
            }
        }

        $jadwalHariIni = collect();
        if ($lokasiFilter) {
            $userIdsLokasi = User::where('lokasi_dinas', $lokasiFilter)->pluck('id');
            $jadwalHariIni = JadwalShift::whereIn('user_id', $userIdsLokasi)
                ->where('tanggal', today())->with('user')->orderBy('shift')->get();
        } elseif ($user->hasAdminAccess()) {
            $jadwalHariIni = JadwalShift::where('tanggal', today())->with('user')->orderBy('shift')->get();
        }

        return view('dashboard.transmisi', compact(
            'pemancars','lokasiFilter','lokasiList',
            'totalLogHariIni','totalLogBulanIni','totalEvidenBulanIni',
            'vswrBermasalah','peringatanPencatatan','jadwalHariIni'
        ));
    }

    // ── Dashboard Sarana & Prasarana ──
    private function dashboardSarana(Request $request, $user)
    {
        $bulan = now()->month;
        $tahun = now()->year;

        $gensetLogs = collect();
        if (class_exists(\App\Models\GensetLog::class)) {
            $query = \App\Models\GensetLog::with(['gensetUnit','user'])
                ->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)
                ->orderByDesc('tanggal');
            $gensetLogs = $query->take(10)->get();
        }

        $statGenset = [
            'total_operasi'  => \App\Models\GensetLog::whereMonth('tanggal',$bulan)->whereYear('tanggal',$tahun)->count(),
            'total_bbm'      => \App\Models\GensetLog::whereMonth('tanggal',$bulan)->whereYear('tanggal',$tahun)->get()->sum('pemakaian_bbm'),
            'total_jam'      => \App\Models\GensetLog::whereMonth('tanggal',$bulan)->whereYear('tanggal',$tahun)->get()->sum(fn($l)=>$l->jam_operasi_hm ?? $l->durasi_jam ?? 0),
            'gangguan'       => \App\Models\GensetLog::whereMonth('tanggal',$bulan)->whereYear('tanggal',$tahun)->where('kondisi','gangguan')->count(),
        ];

        $evidenTerbaru = Eviden::where('divisi','sarana')
            ->orderByDesc('tanggal')->take(5)->get();
        $evidenBulanIni = Eviden::where('divisi','sarana')
            ->whereMonth('tanggal',$bulan)->whereYear('tanggal',$tahun)->count();

        $jadwalHariIni = JadwalShift::whereHas('user', fn($q) => $q->where('divisi','sarana'))
            ->where('tanggal', today())->with('user')->orderBy('shift')->get();

        $peringatanPencatatan = false;

        return view('dashboard.sarana', compact(
            'gensetLogs','statGenset','evidenTerbaru','evidenBulanIni',
            'jadwalHariIni','bulan','tahun','peringatanPencatatan'
        ));
    }

    // ── Dashboard Studio ──
    private function dashboardStudio(Request $request, $user)
    {
        $bulan = now()->month;
        $tahun = now()->year;

        $evidenTerbaru  = Eviden::where('divisi','studio')
            ->with('user')->orderByDesc('tanggal')->take(8)->get();
        $evidenBulanIni = Eviden::where('divisi','studio')
            ->whereMonth('tanggal',$bulan)->whereYear('tanggal',$tahun)->count();
        $evidenHariIni  = Eviden::where('divisi','studio')
            ->whereDate('tanggal', today())->count();

        $statEviden = [
            'bulan_ini'   => $evidenBulanIni,
            'hari_ini'    => $evidenHariIni,
            'total'       => Eviden::where('divisi','studio')->count(),
        ];

        $jadwalHariIni = JadwalShift::whereHas('user', fn($q) => $q->where('divisi','studio'))
            ->where('tanggal', today())->with('user')->orderBy('shift')->get();

        $peringatanPencatatan = false;

        return view('dashboard.studio', compact(
            'evidenTerbaru','statEviden','jadwalHariIni',
            'bulan','tahun','peringatanPencatatan'
        ));
    }
}
