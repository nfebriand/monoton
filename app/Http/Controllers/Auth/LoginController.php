<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\JadwalShift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

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

        if (!Auth::attempt($credentials)) {
            return back()->withErrors(['email' => 'Email atau password salah.'])->withInput();
        }

        $user = Auth::user();

        // Admin: selalu boleh masuk
        if ($user->isAdmin()) {
            $request->session()->regenerate();
            return redirect()->intended('/');
        }

        // Operator: cek jadwal shift dengan toleransi 30 menit
        $shiftAktif = $this->cariShiftAktif($user);

        if (!$shiftAktif) {
            Auth::logout();

            // Tampilkan info shift berikutnya
            $shiftBerikutnya = $this->shiftBerikutnya($user);
            $pesanTambahan   = '';
            if ($shiftBerikutnya) {
                $sd = JadwalShift::getShiftData(
                    $shiftBerikutnya->skema ?? 'default',
                    $shiftBerikutnya->shift
                );
                $label = $sd['label'] ?? 'Shift '.$shiftBerikutnya->shift;
                $tgl   = Carbon::parse($shiftBerikutnya->tanggal)->translatedFormat('d F Y');
                $pesanTambahan = " Jadwal berikutnya: {$label} ({$shiftBerikutnya->jam_mulai}–{$shiftBerikutnya->jam_selesai}) pada {$tgl}.";
            }

            return back()->withErrors([
                'email' => 'Tidak ada jadwal shift aktif saat ini. '.
                           'Login diizinkan 30 menit sebelum dan sesudah jadwal dinas.'.$pesanTambahan
            ])->withInput();
        }

        $request->session()->regenerate();
        $request->session()->put('shift_aktif_id',    $shiftAktif->id);
        $request->session()->put('shift_aktif_no',    $shiftAktif->shift);
        $request->session()->put('shift_aktif_label', $shiftAktif->shift_label);
        $request->session()->put('shift_aktif_skema', $shiftAktif->skema ?? 'default');

        return redirect()->intended('/');
    }

    /**
     * Cari shift aktif dengan toleransi 30 menit
     */
    private function cariShiftAktif(User $user): ?JadwalShift
    {
        $toleransi = 30; // menit

        // Cek kemarin, hari ini, besok (crossing midnight tolerance)
        $tanggals = [
            now()->subDay()->toDateString(),
            now()->toDateString(),
            now()->addDay()->toDateString(),
        ];

        $jadwals = JadwalShift::where('user_id', $user->id)
            ->whereIn('tanggal', $tanggals)
            ->get();

        foreach ($jadwals as $jadwal) {
            if ($jadwal->isAktifSekarang($toleransi)) {
                return $jadwal;
            }
        }

        return null;
    }

    /**
     * Cari shift berikutnya (untuk info di pesan error)
     */
    private function shiftBerikutnya(User $user): ?JadwalShift
    {
        return JadwalShift::where('user_id', $user->id)
            ->where('tanggal', '>=', now()->toDateString())
            ->orderBy('tanggal')->orderBy('jam_mulai')
            ->first();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
