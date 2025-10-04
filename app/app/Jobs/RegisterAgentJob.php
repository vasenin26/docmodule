<?php

namespace App\Jobs;

use App\Common\DTO\RemoteAgent\ConfigOptions;
use App\Interfaces\AgentOrchestratorInterface;
use App\Models\Agent;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

/**
 * Фоновая задача для регистрации агента в оркестраторе
 * 
 * Выполняется асинхронно после создания агента. Вызывает оркестратор
 * для регистрации агента и сохраняет полученный публичный ключ в базу данных.
 */
class RegisterAgentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Количество попыток выполнения задачи
     */
    public int $tries = 3;

    /**
     * Таймаут выполнения задачи в секундах
     */
    public int $timeout = 300;

    /**
     * ID агента для регистрации
     */
    private int $agentId;

    /**
     * Создать новый экземпляр задачи
     * 
     * @param int $agentId ID созданного агента
     */
    public function __construct(int $agentId)
    {
        $this->agentId = $agentId;
    }

    /**
     * Выполнить задачу
     * 
     * @param AgentOrchestratorInterface $orchestrator Сервис оркестратора агентов
     */
    public function handle(AgentOrchestratorInterface $orchestrator): void
    {
        try {
            Log::info('RegisterAgentJob: Starting agent registration', [
                'agent_id' => $this->agentId,
            ]);

            // 1. Загружаем модель Agent по ID
            $agent = Agent::findOrFail($this->agentId);

            // 2. Проверяем наличие UUID (должен быть создан при создании агента)
            if (!$agent->uuid) {
                throw new \Exception('Agent UUID is missing. UUID must be generated when creating agent.');
            }

            // 3. Преобразуем строковый UUID в объект Uuid для ConfigOptions
            $agentUuid = Uuid::fromString($agent->uuid);

            Log::info('RegisterAgentJob: Using agent UUID', [
                'agent_id' => $this->agentId,
                'agent_uuid' => $agentUuid->toString(),
            ]);

            // 4. Создаем ConfigOptions с параметрами агента
            $configOptions = new ConfigOptions(
                agentId: $agentUuid,
                token: $agent->token,
            );

            // 5. Вызываем оркестратор для регистрации агента
            $agentMeta = $orchestrator->startAgent($configOptions);

            Log::info('RegisterAgentJob: Received agent metadata from orchestrator', [
                'agent_id' => $this->agentId,
                'server' => $agentMeta->server,
                'public_key_length' => strlen($agentMeta->publicKey),
            ]);

            // 6. Сохраняем публичный ключ в базу данных
            $agent->update([
                'public_key' => $agentMeta->publicKey,
            ]);

            Log::info('RegisterAgentJob: Agent registered successfully', [
                'agent_id' => $this->agentId,
                'agent_uuid' => $agentUuid->toString(),
                'public_key_saved' => true,
            ]);

        } catch (Exception $e) {
            Log::error('RegisterAgentJob: Failed to register agent', [
                'agent_id' => $this->agentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts(),
            ]);

            // Если это последняя попытка, логируем финальную ошибку
            if ($this->attempts() >= $this->tries) {
                Log::critical('RegisterAgentJob: All attempts failed', [
                    'agent_id' => $this->agentId,
                    'total_attempts' => $this->attempts(),
                ]);
            }

            // Пробрасываем исключение для автоматического повтора
            throw $e;
        }
    }

    /**
     * Обработка неудачного выполнения задачи
     * 
     * @param Exception $exception Исключение, вызвавшее ошибку
     */
    public function failed(Exception $exception): void
    {
        Log::critical('RegisterAgentJob: Job failed permanently', [
            'agent_id' => $this->agentId,
            'error' => $exception->getMessage(),
        ]);

        // Здесь можно добавить дополнительную логику:
        // - Отправить уведомление администратору
        // - Пометить агента как требующего ручной регистрации
        // - Сохранить информацию об ошибке в отдельную таблицу
    }
}
