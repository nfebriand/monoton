<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Pengguna yang wajib ganti password (password standar / direset admin) hanya boleh membuka halaman ganti password. */
class ForcePasswordChange
{
    private const BOLEH = ['users.change-password', 'users.update-password', 'logout', 'offline'];

    public function handle(Request $request, Closure $next): Response
    {
        $u = $request->user();
        if (!$u || !$u->must_change_password) return $next($request);
        if ($request->routeIs(...self::BOLEH)) return $next($request);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Anda wajib mengganti password terlebih dahulu.'], 423);
        }
        return redirect()->route('users.change-password')
            ->with('warning', 'Anda masih memakai password standar. Ganti password terlebih dahulu sebelum memakai aplikasi.');
    }
}
