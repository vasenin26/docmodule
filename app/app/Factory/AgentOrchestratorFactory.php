<?php

namespace App\Factory;

use App\Interfaces\AgentOrchestratorInterface;
use App\Services\AgentOrchestrator\FakeAgentOrchestratorService;
use App\Services\AgentOrchestrator\RemoteAgentOrchestratorService;
use Illuminate\Support\Facades\Log;

/**
 * Фабрика для создания экземпляра оркестратора агентов
 * 
 * Определяет, какую реализацию использовать на основе конфигурации.
 * - Если AGENT_SERVER не задан - использует заглушку (Fake)
 * - Если AGENT_SERVER задан - использует реальный HTTP клиент (Remote)
 */
class AgentOrchestratorFactory
{
    public static function create(): AgentOrchestratorInterface
    {
        $serverUrl = config('services.agent_orchestrator.server_url');
        $timeout = config('services.agent_orchestrator.timeout', 30);

        // Если AGENT_SERVER не задан или равен дефолтному значению - используем заглушку
        if (empty($serverUrl) || $serverUrl === 'http://localhost:8080') {
            Log::info('AgentOrchestratorFactory: Using FakeAgentOrchestratorService');
            return new FakeAgentOrchestratorService();
        }

        Log::info('AgentOrchestratorFactory: Using RemoteAgentOrchestratorService', [
            'server_url' => $serverUrl,
            'timeout' => $timeout,
        ]);

        return new RemoteAgentOrchestratorService($serverUrl, $timeout);
    }
}

