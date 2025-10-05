<?php

namespace App\Jobs;

use App\Interfaces\AgentOrchestratorInterface;
use App\Models\AgentTask;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Фоновая задача для уведомления оркестратора о создании AgentTask
 * 
 * Выполняется асинхронно после создания задачи агента. Вызывает оркестратор
 * через метод startProcess() для запуска обработки.
 */
class SendAgentTaskToOrchestratorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Количество попыток выполнения задачи
     */
    public int $tries = 3;

    /**
     * Таймаут выполнения задачи в секундах
     */
    public int $timeout = 30;

    /**
     * Время задержки между попытками (в секундах)
     * Использует экспоненциальную стратегию: [10, 20, 40]
     */
    public function backoff(): array
    {
        return [10, 20, 40];
    }

    /**
     * ID задачи агента
     */
    private int $taskId;

    /**
     * Создать новый экземпляр задачи
     * 
     * @param int $taskId ID созданной задачи агента
     */
    public function __construct(int $taskId)
    {
        $this->taskId = $taskId;
    }

    /**
     * Выполнить задачу
     * 
     * @param AgentOrchestratorInterface $orchestrator Сервис оркестратора агентов
     */
    public function handle(AgentOrchestratorInterface $orchestrator): void
    {
        try {
            Log::info('SendAgentTaskToOrchestratorJob: Starting', [
                'task_id' => $this->taskId,
                'attempt' => $this->attempts(),
            ]);

            // 1. Загружаем модель AgentTask по ID
            $task = AgentTask::find($this->taskId);

            // Edge case: задача удалена до выполнения Job
            if (!$task) {
                Log::warning('SendAgentTaskToOrchestratorJob: Task not found, skipping', [
                    'task_id' => $this->taskId,
                ]);
                return; // Не бросаем исключение, успешно завершаем Job
            }

            // Edge case: задача уже завершена/отменена
            if ($task->isFinished()) {
                Log::info('SendAgentTaskToOrchestratorJob: Task already finished, skipping', [
                    'task_id' => $this->taskId,
                    'status' => $task->status,
                ]);
                return;
            }

            // 2. Извлекаем тип задачи (enum → string)
            $taskType = $task->type->value;

            Log::info('SendAgentTaskToOrchestratorJob: Loaded task details', [
                'task_id' => $this->taskId,
                'task_type' => $taskType,
                'project_id' => $task->project_id,
                'chat_id' => $task->chat_id,
                'status' => $task->status,
            ]);

            // 3. Вызываем оркестратор для запуска обработки
            $orchestrator->startProcess($taskType);

            Log::info('SendAgentTaskToOrchestratorJob: Successfully notified orchestrator', [
                'task_id' => $this->taskId,
                'task_type' => $taskType,
            ]);

        } catch (Exception $e) {
            Log::error('SendAgentTaskToOrchestratorJob: Failed to notify orchestrator', [
                'task_id' => $this->taskId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts(),
                'max_attempts' => $this->tries,
            ]);

            // Если это последняя попытка, логируем критическую ошибку
            if ($this->attempts() >= $this->tries) {
                Log::critical('SendAgentTaskToOrchestratorJob: All attempts exhausted', [
                    'task_id' => $this->taskId,
                    'total_attempts' => $this->attempts(),
                    'final_error' => $e->getMessage(),
                ]);
            }

            // Пробрасываем исключение для автоматического повтора
            throw $e;
        }
    }

    /**
     * Обработка окончательного провала задачи
     * 
     * Вызывается после исчерпания всех попыток
     * 
     * @param Exception $exception Исключение, вызвавшее ошибку
     */
    public function failed(Exception $exception): void
    {
        Log::critical('SendAgentTaskToOrchestratorJob: Job failed permanently', [
            'task_id' => $this->taskId,
            'error' => $exception->getMessage(),
        ]);

        // Здесь можно добавить дополнительную логику:
        // - Отправить уведомление администратору
        // - Пометить задачу как требующую ручного вмешательства
        // - Сохранить информацию об ошибке в отдельную таблицу
        // - Отправить метрику в систему мониторинга
    }

    /**
     * Получить теги для идентификации задачи в очереди
     * 
     * Используется для мониторинга и фильтрации в Horizon/других UI
     */
    public function tags(): array
    {
        return ['orchestrator', 'agent-task', "task:{$this->taskId}"];
    }
}

