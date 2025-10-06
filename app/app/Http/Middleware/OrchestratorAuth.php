<?php

namespace App\Http\Middleware;

use App\Services\AgentJwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class OrchestratorAuth
{
    public function __construct(
        private AgentJwtService $jwtService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            Log::warning('Orchestrator API: Token not provided', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);
            
            return response()->json([
                'error' => 'Token required'
            ], 401);
        }

        $agent = $this->jwtService->validateToken($token);

        if (!$agent) {
            Log::warning('Orchestrator API: Invalid token', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);
            
            return response()->json([
                'error' => 'Invalid or expired token'
            ], 401);
        }

        // НЕ проверяем флаг has_cross_project_access здесь
        // Это делается в сервисе при выборке задач
        
        // Добавляем агента в request для использования в контроллерах
        $request->merge(['agent' => $agent]);

        Log::debug('Orchestrator API: Agent authenticated', [
            'agent_id' => $agent->id,
            'agent_name' => $agent->name,
            'has_cross_project_access' => $agent->has_cross_project_access,
        ]);

        return $next($request);
    }
}
