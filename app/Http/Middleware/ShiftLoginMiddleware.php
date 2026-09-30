<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShiftLoginMiddleware
{
    /**
     * Pengecekan jadwal shift untuk login telah dinonaktifkan.
     * User dapat login kapan pun selama akun aktif.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
