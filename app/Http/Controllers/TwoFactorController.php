<?php

namespace App\Http\Controllers;

use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class TwoFactorController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $secret = $uri = null;
        if (!$user->hasTwoFactor()) {
            // Rahasia sementara disimpan di session sampai kode pertama terkonfirmasi
            $secret = $request->session()->get('2fa_setup_secret');
            if (!$secret) {
                $secret = Totp::generateSecret();
                $request->session()->put('2fa_setup_secret', $secret);
            }
            $uri = Totp::uri($secret, $user->email);
        }
        return view('users.two-factor', compact('user', 'secret', 'uri'));
    }

    public function aktifkan(Request $request)
    {
        $request->validate(['kode' => 'required|string']);
        $user   = $request->user();
        $secret = $request->session()->get('2fa_setup_secret');
        if (!$secret) return redirect()->route('two-factor.show');

        $step = Totp::verify($secret, $request->kode);
        if ($step === null) {
            return back()->withErrors(['kode' => 'Kode tidak cocok. Pastikan jam HP akurat lalu coba lagi.']);
        }
        $user->forceFill([
            'two_factor_secret'       => Crypt::encryptString($secret),
            'two_factor_confirmed_at' => now(),
            'two_factor_last_step'    => $step,
        ])->save();
        $request->session()->forget('2fa_setup_secret');

        return redirect()->route('two-factor.show')->with('success', '2FA berhasil diaktifkan. Anda kini dapat memulihkan password sendiri lewat "Lupa password?".');
    }

    public function nonaktifkan(Request $request)
    {
        $request->validate(['password' => 'required']);
        $user = $request->user();
        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Password tidak sesuai.']);
        }
        $user->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null, 'two_factor_last_step' => null])->save();
        return redirect()->route('two-factor.show')->with('success', '2FA dinonaktifkan.');
    }
}
