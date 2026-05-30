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
        $user         = auth()->user();
        $today        = now()->toDateString();
        $filterLokasi = $request->get('lokasi');
        $semuaLokasi  = Pemancar::whereNotNull('lokasi')->distinct()->orderBy('lokasi')->pluck('lokasi');

        // ── Tentukan scope lokasi berdasarkan role ──
        $lokasiScope = null;
        if ($user->isOperator() && $user->lokasi_dinas) {
            $lokasiScope = $user->lokasi_dinas; // operator: paksa lokasi dinasnya
        } elseif ($user->isAdmin() && $filterLokasi) {
            $lokasiScope = $filterLokasi; // admin: ikut filter
        }

        // ── Pemancar sesuai scope ──
        $pemancarQuery = Pemancar::with(['operasionalLogs' => fn($q) =>
            $q->with('user')->latest('dicatat_pada')->limit(1)
        ])->where('is_active', true);
        if ($lokasiScope) $pemancarQuery->where('lokasi', $lokasiScope);
        $pemancars = $pemancarQuery->orderBy('nama_stasiun')->get();

        // ── Statistik: hanya hitung berdasarkan lokasi scope ──
        $pemancarIds = $pemancars->pluck('id');

        $stats = [
            'total_pemancar'  => $pemancarIds->count(),
            'total_operator'  => $lokasiScope
                ? User::where('role','operator')->where('is_active',true)->where('lokasi_dinas',$lokasiScope)->count()
                : User::where('role','operator')->where('is_active',true)->count(),
            'log_hari_ini'    => $pemancarIds->isNotEmpty()
                ? OperasionalLog::whereDate('dicatat_pada',$today)->whereIn('pemancar_id',$pemancarIds)->count()
                : 0,
            'shift_hari_ini'  => JadwalShift::where('tanggal',$today)->count(),
        ];

        // ── Jadwal hari ini ──
        $jadwalHariIni = JadwalShift::with('user')
            ->where('tanggal',$today)->orderBy('shift')->get();

        // ── Peringatan operator ──
        $peringatanPencatatan = false;
        if ($user->isOperator()) {
            $shiftAktifId = session('shift_aktif_id');
            if ($shiftAktifId) {
                $lastLog = OperasionalLog::where('jadwal_shift_id',$shiftAktifId)
                    ->latest('dicatat_pada')->first();
                if (!$lastLog || $lastLog->dicatat_pada->diffInMinutes(now()) >= 180) {
                    $peringatanPencatatan = true;
                }
            }
        }

        // ── Grafik 7 hari ──
        $grafikData = collect(range(6,0))->map(function($d) use ($pemancarIds) {
            $date  = now()->subDays($d)->toDateString();
            $query = OperasionalLog::whereDate('dicatat_pada',$date);
            if ($pemancarIds->isNotEmpty()) $query->whereIn('pemancar_id',$pemancarIds);
            return ['tanggal'=>now()->subDays($d)->format('d/m'), 'count'=>$query->count()];
        });

        return view('dashboard', compact(
            'stats','pemancars','jadwalHariIni',
            'peringatanPencatatan','grafikData',
            'semuaLokasi','filterLokasi','lokasiScope'
        ));
    }
}
