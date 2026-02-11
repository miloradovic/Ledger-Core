<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class HealthController extends Controller
{
    /**
     * Basic health check endpoint
     */
    public function ping(): JsonResponse
    {
        return response()->json(['status' => 'ok'], Response::HTTP_OK);
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
        } catch (Throwable $e) {
            $health['status'] = 'error';
            $health['database'] = 'disconnected';
        }

        return response()->json(
            $health,
            $health['status'] === 'ok' ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE
        );
    }
}
