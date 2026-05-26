<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\JadwalShift;
use Symfony\Component\HttpFoundation\Response;

class ShiftLoginMiddleware
{
    /**
     * Validasi bahwa user hanya bisa login/akses pada shift yang ditentukan.
     * Admin selalu diizinkan.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Admin bebas akses kapan saja
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Cek apakah user punya jadwal shift hari ini
        $today = now()->toDateString();
        $nowTime = now()->format('H:i');

        $jadwal = JadwalShift::where('user_id', $user->id)
            ->where('tanggal', $today)
            ->get();

        if ($jadwal->isEmpty()) {
            auth()->logout();
            $request->session()->invalidate();
            return redirect()->route('login')
                ->withErrors(['shift' => 'Anda tidak memiliki jadwal shift hari ini.']);
        }

        // Cek apakah waktu sekarang dalam rentang salah satu shift
        $shiftAktif = $jadwal->filter(function ($j) use ($nowTime) {
            return $nowTime >= $j->jam_mulai && $nowTime < $j->jam_selesai;
        });

        if ($shiftAktif->isEmpty()) {
            // Tampilkan pesan shift berikutnya
            $shiftBerikutnya = $jadwal->filter(function ($j) use ($nowTime) {
                return $nowTime < $j->jam_mulai;
            })->sortBy('jam_mulai')->first();

            $msg = 'Bukan waktu shift Anda saat ini.';
            if ($shiftBerikutnya) {
                $msg .= " Shift Anda berikutnya dimulai pukul {$shiftBerikutnya->jam_mulai}.";
            }

            auth()->logout();
            $request->session()->invalidate();
            return redirect()->route('login')->withErrors(['shift' => $msg]);
        }

        // Simpan info shift aktif ke session
        $request->session()->put('shift_aktif_id', $shiftAktif->first()->id);
        $request->session()->put('shift_aktif_no', $shiftAktif->first()->shift);

        return $next($request);
    }
}
