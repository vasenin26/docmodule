<?php

namespace App\Jobs;

use App\Interfaces\Factory\AgentFactoryInterface;
use App\Models\Techplane;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateTechplaneJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    public $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $techplaneId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AgentFactoryInterface $agentFactory): void
    {
        $techplane = Techplane::with(['task', 'task.page'])->findOrFail($this->techplaneId);

        try {
            // Устанавливаем статус "generating"
            $techplane->update([
                'generation_status' => Techplane::STATUS_GENERATING
            ]);

            $task = $techplane->task;
            
            if (!$task) {
                throw new Exception('Task not found for techplane');
            }

            // Получаем описание задачи
            $taskDescription = $task->content ?? 'Описание задачи отсутствует';

            // Получаем генератор техплана
            $projectId = $task->page ? $task->page->project_id : null;
            $techplaneGenerator = $agentFactory->getTechplaneGenerator($projectId);
            
            // Генерируем техплан
            $generationResult = $techplaneGenerator->generate($taskDescription);

            // Сохраняем сгенерированный техплан и обновляем статус
            $techplane->update([
                'content' => $generationResult->result,
                'generation_status' => Techplane::STATUS_COMPLETED,
                'chat_id' => $generationResult->chatId
            ]);

        } catch (Exception $e) {
            // Логируем ошибку
            Log::error('Failed to generate techplane', [
                'techplane_id' => $this->techplaneId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Устанавливаем статус "failed"
            $techplane->update([
                'generation_status' => Techplane::STATUS_FAILED
            ]);

            // Перебрасываем исключение для обработки системой очередей
            throw $e;
        }
    }
}
