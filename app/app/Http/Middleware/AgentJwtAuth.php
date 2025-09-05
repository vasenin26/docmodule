<?php

namespace App\Http\Middleware;

use App\Models\Agent;
use App\Services\AgentJwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AgentJwtAuth
{
    public function __construct(
        private AgentJwtService $jwtService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Token required'], 401);
        }

        $agent = $this->jwtService->validateToken($token);

        if (!$agent) {
            return response()->json(['error' => 'Invalid or expired token'], 401);
        }

        // Добавляем агента в request для использования в контроллерах
        $request->merge(['agent' => $agent]);

        return $next($request);
    }
}
