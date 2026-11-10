<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PasswordPolicy;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** Lupa password: email -> kode 2FA (aplikasi authenticator) -> password baru. */
class ForgotPasswordController extends Controller
{
    private const BERLAKU_MENIT = 10;

    public function email()
    {
        return view('auth.lupa-password', ['langkah' => 'email']);
    }

    public function emailPost(Request $request)
    {
        $v = $request->validate(['email' => 'required|email']);
        // Selalu lanjut ke langkah kode agar keberadaan akun tidak bisa ditebak
        $request->session()->put('lp_email', mb_strtolower($v['email']));
        return redirect()->route('password.forgot.kode');
    }

    public function kode(Request $request)
    {
        if (!$request->session()->has('lp_email')) return redirect()->route('password.forgot');
        return view('auth.lupa-password', ['langkah' => 'kode']);
    }

    public function kodePost(Request $request)
    {
        $email = $request->session()->get('lp_email');
        if (!$email) return redirect()->route('password.forgot');
        $request->validate(['kode' => 'required|string']);

        $user = User::whereRaw('LOWER(email) = ?', [$email])->where('is_active', true)->first();
        $step = null;
        if ($user && $user->hasTwoFactor() && ($secret = $user->twoFactorSecretPlain())) {
            $step = Totp::verify($secret, $request->kode, $user->two_factor_last_step);
        }
        if ($step === null) {
            return back()->withErrors(['kode' => 'Kode tidak valid, atau akun ini belum mengaktifkan 2FA. Bila 2FA belum aktif / HP hilang, hubungi administrator untuk reset password.']);
        }

        $user->forceFill(['two_factor_last_step' => $step])->save();
        $request->session()->forget('lp_email');
        $request->session()->put('lp_user', $user->id);
        $request->session()->put('lp_until', now()->addMinutes(self::BERLAKU_MENIT)->timestamp);
        return redirect()->route('password.forgot.baru');
    }

    private function userReset(Request $request): ?User
    {
        $id = $request->session()->get('lp_user');
        $until = (int) $request->session()->get('lp_until', 0);
        if (!$id || $until < time()) {
            $request->session()->forget(['lp_user', 'lp_until']);
            return null;
        }
        return User::where('is_active', true)->find($id);
    }

    public function baru(Request $request)
    {
        if (!$this->userReset($request)) {
            return redirect()->route('password.forgot')->withErrors(['email' => 'Sesi pemulihan berakhir. Ulangi dari awal.']);
        }
        return view('auth.lupa-password', ['langkah' => 'baru']);
    }

    public function baruPost(Request $request)
    {
        $user = $this->userReset($request);
        if (!$user) {
            return redirect()->route('password.forgot')->withErrors(['email' => 'Sesi pemulihan berakhir. Ulangi dari awal.']);
        }
        $request->validate(['password' => 'required|min:8|confirmed']);

        if (PasswordPolicy::standar($request->password)) {
            return back()->withErrors(['password' => 'Password baru tidak boleh sama dengan password standar.']);
        }
        if (Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Password baru harus berbeda dengan password lama.']);
        }

        $user->forceFill([
            'password'             => Hash::make($request->password),
            'must_change_password' => false,
            'password_changed_at'  => now(),
            'remember_token'       => Str::random(60),
        ])->save();
        $request->session()->forget(['lp_user', 'lp_until']);

        return redirect()->route('login')->with('status', 'Password berhasil diganti. Silakan masuk dengan password baru.');
    }
}
