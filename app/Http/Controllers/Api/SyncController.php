<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    /**
     * Terima POST dari offline sync — sama persis dengan controller biasa
     * tapi return JSON response
     */
    public function syncLog(Request $request)
    {
        try {
            $controller = app(\App\Http\Controllers\OperasionalController::class);
            $result     = $controller->store($request);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Offline sync log error: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function syncEviden(Request $request)
    {
        try {
            $controller = app(\App\Http\Controllers\EvidenController::class);
            $result     = $controller->store($request);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Offline sync eviden error: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Status untuk sync manager
     */
    public function status()
    {
        return response()->json([
            'online'  => true,
            'server'  => 'MonOTOn',
            'time'    => now()->format('Y-m-d H:i:s'),
            'version' => \App\Models\AppSetting::get('app_version','1.0.0'),
        ]);
    }
}
