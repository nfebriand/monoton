<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pengaman sinkronisasi offline.
 *
 * Browser mengirim ulang data yang tersimpan offline dengan header X-Offline-Id (UUID unik per data).
 *  - ID yang sudah pernah diproses  -> dijawab "ok" tanpa menyimpan lagi (mencegah data ganda
 *    bila respons pertama hilang di jalan lalu browser mengirim ulang).
 *  - Respons dibuat JSON: sukses = 200 {ok:true}; ditolak validasi/aturan = 422 {message,errors}.
 * Tanpa header ini middleware tidak berbuat apa-apa (form biasa tidak terpengaruh).
 */
class OfflineIdempotency
{
    public function handle(Request $request, Closure $next)
    {
        $id = (string) $request->header('X-Offline-Id', '');
        if ($id === '' || !$request->isMethod('POST') || !preg_match('/^[A-Za-z0-9-]{8,64}$/', $id)) {
            return $next($request);
        }
        if (!Schema::hasTable('offline_sync_ids')) {
            return $next($request);
        }

        try {
            DB::table('offline_sync_ids')->insert([
                'uuid'       => $id,
                'user_id'    => optional($request->user())->id,
                'uri'        => substr($request->path(), 0, 190),
                'created_at' => now(),
            ]);
        } catch (QueryException $e) {
            // UUID sudah ada = data ini sudah pernah masuk
            return response()->json(['ok' => true, 'duplikat' => true]);
        }

        try {
            $res = $next($request);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->lepas($id);
            return response()->json(['ok' => false, 'message' => 'Data tidak valid.', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->lepas($id);
            throw $e;
        }

        $status = $res->getStatusCode();
        if ($status >= 400) {
            $this->lepas($id);
            return $res;
        }

        // Controller yang menolak lewat back()->withErrors()/with('error') tetap 302: perlakukan sebagai gagal
        if ($request->hasSession()) {
            $session = $request->session();
            $errors  = $session->get('errors');
            $pesanGagal = $session->get('error');
            if (($errors && method_exists($errors, 'any') && $errors->any()) || $pesanGagal) {
                $this->lepas($id);
                $semua = ($errors && method_exists($errors, 'getBag')) ? $errors->getBag('default')->toArray() : [];
                $session->forget(['errors', 'error']);
                return response()->json([
                    'ok'      => false,
                    'message' => $pesanGagal ?: (collect($semua)->flatten()->first() ?: 'Data ditolak server.'),
                    'errors'  => $semua,
                ], 422);
            }
        }

        if (mt_rand(1, 50) === 1) {
            DB::table('offline_sync_ids')->where('created_at', '<', now()->subDays(60))->delete();
        }

        return response()->json(['ok' => true]);
    }

    private function lepas(string $id): void
    {
        try { DB::table('offline_sync_ids')->where('uuid', $id)->delete(); } catch (\Throwable $e) {}
    }
}
