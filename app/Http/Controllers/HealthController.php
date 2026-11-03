<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class HealthController extends Controller
{
    /**
     * Liveness probe: the process is up and can serve a request.
     */
    public function live(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    /**
     * Readiness probe: the process is up and its dependencies answer.
     */
    public function ready(): JsonResponse
    {
        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'The database connection failed.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return response()->json(['status' => 'ok']);
    }
}
