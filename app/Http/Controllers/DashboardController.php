<?php

namespace App\Http\Controllers;

use App\Models\Pemancar;
use App\Models\OperasionalLog;
use App\Models\JadwalShift;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user       = auth()->user();
        $today      = now()->toDateString();
        $filterLokasi = $request->get('lokasi');

        // Daftar lokasi unik dari pemancar
        $semuaLokasi = Pemancar::whereNotNull('lokasi')->distinct()->pluck('lokasi')->sort()->values();

        // Query pemancar sesuai role dan filter
        $pemancarQuery = Pemancar::with(['operasionalLogs' => fn($q) =>
            $q->with('user')->latest('dicatat_pada')->limit(1)
        ])->where('is_active', true);

        if ($user->isOperator() && $user->lokasi_dinas) {
            $pemancarQuery->where('lokasi', $user->lokasi_dinas);
        } elseif ($filterLokasi) {
            $pemancarQuery->where('lokasi', $filterLokasi);
        }

        $pemancars = $pemancarQuery->orderBy('nama_stasiun')->get();

        // Statistik
        $stats = [
            'total_pemancar' => Pemancar::where('is_active', true)->count(),
            'total_operator' => User::where('role', 'operator')->where('is_active', true)->count(),
            'log_hari_ini'   => OperasionalLog::whereDate('dicatat_pada', $today)->count(),
            'shift_hari_ini' => JadwalShift::where('tanggal', $today)->count(),
        ];

        // Jadwal hari ini
        $jadwalHariIni = JadwalShift::with('user')
            ->where('tanggal', $today)->orderBy('shift')->get();

        // Peringatan pencatatan untuk operator
        $peringatanPencatatan = false;
        if ($user->isOperator()) {
            $shiftAktifId = session('shift_aktif_id');
            if ($shiftAktifId) {
                $lastLog = OperasionalLog::where('jadwal_shift_id', $shiftAktifId)
                    ->latest('dicatat_pada')->first();
                if (!$lastLog || $lastLog->dicatat_pada->diffInMinutes(now()) >= 180) {
                    $peringatanPencatatan = true;
                }
            }
        }

        // Grafik 7 hari
        $grafikData = collect(range(6, 0))->map(fn($d) => [
            'tanggal' => now()->subDays($d)->format('d/m'),
            'count'   => OperasionalLog::whereDate('dicatat_pada', now()->subDays($d)->toDateString())->count(),
        ]);

        return view('dashboard', compact(
            'stats', 'pemancars', 'jadwalHariIni',
            'peringatanPencatatan', 'grafikData',
            'semuaLokasi', 'filterLokasi'
        ));
    }
}
