<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AiOrchestrationKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = config('services.ai_orchestration.key');
        $providedKey = $request->header('X-AI-Orchestration-Key', '');

        if (! is_string($configuredKey) || $configuredKey === '') {
            return response()->json([
                'message' => 'AI orchestration API is not configured.',
            ], 503);
        }

        if (! is_string($providedKey) || $providedKey === '' || ! hash_equals($configuredKey, $providedKey)) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        return $next($request);
    }
}
