<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Mengizinkan akses untuk Super Admin DAN Admin Divisi.
 * Scoping data per-divisi (agar admin divisi hanya melihat
 * data divisinya sendiri) dilakukan di masing-masing controller
 * menggunakan helper: auth()->user()->canManageDivisi($divisi)
 */
class AdminOrDivisiAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user || !$user->hasAdminAccess()) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
