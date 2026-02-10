<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Basic health check endpoint
     */
    public function ping(): JsonResponse
    {
        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * Detailed health check with database connectivity
     */
    public function check(): JsonResponse
    {
        $health = [
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ];

        // Check database connectivity
        try {
            DB::connection()->getPdo();
            $health['database'] = 'connected';
        } catch (\Exception $e) {
            $health['status'] = 'error';
            $health['database'] = 'disconnected';
        }

        return response()->json($health, $health['status'] === 'ok' ? 200 : 503);
    }
}
