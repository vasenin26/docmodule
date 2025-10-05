<?php

namespace App\Services\AgentOrchestrator;

use App\Common\DTO\RemoteAgent\AgentMeta;
use App\Common\DTO\RemoteAgent\ConfigOptions;
use App\Services\AgentOrchestrator\HttpClient\RemoteAgentClient;
use App\Interfaces\AgentOrchestratorInterface;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для взаимодействия с внешним оркестратором агентов
 * 
 * Реализует интеграцию с удаленным сервисом управления агентами через HTTP API.
 * Обеспечивает запуск, остановку агентов и запуск процессов обработки задач.
 */
class RemoteAgentOrchestratorService implements AgentOrchestratorInterface
{
    private RemoteAgentClient $client;

    public function __construct(
        string $serverUrl,
        int $timeout = 30
    ) {
        $this->client = new RemoteAgentClient($serverUrl, $timeout);
    }

    /**
     * Запуск агента в оркестраторе
     * 
     * @param ConfigOptions $configOptions Конфигурация агента (UUID, токен)
     * @return AgentMeta Метаданные зарегистрированного агента
     * @throws Exception При ошибке запуска агента
     */
    public function startAgent(ConfigOptions $configOptions): AgentMeta
    {
        try {
            Log::info('RemoteAgentOrchestrator: startAgent called', [
                'agent_id' => $configOptions->agentId->toString(),
            ]);

            $agentMeta = $this->client->startAgent($configOptions);

            Log::info('RemoteAgentOrchestrator: Agent registered successfully', [
                'agent_id' => $agentMeta->agentId,
                'server' => $agentMeta->server,
                'public_key_length' => strlen($agentMeta->publicKey),
            ]);

            return $agentMeta;

        } catch (Exception $e) {
            Log::error('RemoteAgentOrchestrator: Failed to start agent', [
                'agent_id' => $configOptions->agentId->toString(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Остановка агента в оркестраторе
     * 
     * @param AgentMeta $meta Метаданные агента
     * @return AgentMeta Обновленные метаданные агента
     * @throws Exception При ошибке остановки агента
     */
    public function stopAgent(AgentMeta $meta): AgentMeta
    {
        try {
            Log::info('RemoteAgentOrchestrator: stopAgent called', [
                'agent_id' => $meta->agentId,
                'server' => $meta->server,
            ]);

            // Извлекаем agentId из AgentMeta для HTTP запроса
            $this->client->stopAgent($meta->agentId);

            Log::info('RemoteAgentOrchestrator: Agent stopped successfully', [
                'agent_id' => $meta->agentId,
            ]);

            return $meta;

        } catch (Exception $e) {
            Log::error('RemoteAgentOrchestrator: Failed to stop agent', [
                'agent_id' => $meta->agentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Запуск процесса обработки задачи
     * 
     * @param string $taskType Тип задачи для запуска
     * @throws Exception При ошибке запуска процесса
     */
    public function startProcess(string $taskType): void
    {
        try {
            Log::info('RemoteAgentOrchestrator: startProcess called', [
                'task_type' => $taskType,
            ]);

            $this->client->startProcess($taskType);

            Log::info('RemoteAgentOrchestrator: Process started successfully', [
                'task_type' => $taskType,
            ]);

        } catch (Exception $e) {
            Log::error('RemoteAgentOrchestrator: Failed to start process', [
                'task_type' => $taskType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}

