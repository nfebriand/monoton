<?php
namespace App\Http\Middleware;
use Closure;
class PreventRequestsCaching {
    public function handle($request, Closure $next) {
        $response = $next($request);
        return $response->header('Cache-Control','no-store, no-cache, must-revalidate')
                        ->header('Pragma','no-cache')
                        ->header('Expires','0');
    }
}
