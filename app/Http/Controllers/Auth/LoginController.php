<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JadwalShift;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!auth()->attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau password salah.'])->withInput();
        }

        $user = auth()->user();

        if (!$user->is_active) {
            auth()->logout();
            return back()->withErrors(['email' => 'Akun Anda telah dinonaktifkan. Hubungi administrator.']);
        }

        // Validasi shift untuk operator
        if ($user->isOperator()) {
            $today   = now()->toDateString();
            $nowTime = now()->format('H:i');

            $jadwal = JadwalShift::where('user_id', $user->id)
                ->where('tanggal', $today)
                ->where('jam_mulai', '<=', $nowTime)
                ->where('jam_selesai', '>', $nowTime)
                ->first();

            if (!$jadwal) {
                // Cek apakah ada jadwal hari ini tapi di luar jam shift
                $adaJadwalHariIni = JadwalShift::where('user_id', $user->id)
                    ->where('tanggal', $today)
                    ->exists();

                auth()->logout();

                if ($adaJadwalHariIni) {
                    // Tampilkan info shift berikutnya
                    $shiftBerikutnya = JadwalShift::where('user_id', $user->id)
                        ->where('tanggal', $today)
                        ->where('jam_mulai', '>', $nowTime)
                        ->orderBy('jam_mulai')
                        ->first();

                    $msg = 'Bukan jam shift Anda saat ini.';
                    if ($shiftBerikutnya) {
                        $msg .= " Shift Anda berikutnya dimulai pukul {$shiftBerikutnya->jam_mulai}.";
                    }
                    return back()->withErrors(['shift' => $msg])->withInput();
                }

                return back()->withErrors([
                    'shift' => 'Anda tidak memiliki jadwal shift hari ini. Hubungi administrator.'
                ])->withInput();
            }

            // Simpan info shift aktif ke session
            session([
                'shift_aktif_id' => $jadwal->id,
                'shift_aktif_no' => $jadwal->shift,
            ]);
        }

        $request->session()->regenerate();
        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
