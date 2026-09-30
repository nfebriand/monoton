<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Hanya Super Admin dan Divisi Sarana & Prasarana yang boleh lewat.
 * Divisi Transmisi (operator maupun admin_divisi) tidak diizinkan.
 */
class SaranaOrAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $boleh = $user->isAdmin() || $user->isDivisi('sarana');

        if (!$boleh) {
            abort(403, 'Menu ini hanya untuk Divisi Sarana & Prasarana.');
        }

        return $next($request);
    }
}
