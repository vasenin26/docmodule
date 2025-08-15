<?php

namespace App\Jobs;

use App\Models\Actualization;
use App\Services\ActualizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPageActualizationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Количество попыток выполнения задачи
     */
    public int $tries = 3;

    /**
     * Таймаут выполнения задачи в секундах
     */
    public int $timeout = 600;

    /**
     * Создать новый экземпляр задачи
     */
    public function __construct(
        public int $actualizationId
    ) {
    }

    /**
     * Выполнить задачу
     */
    public function handle(ActualizationService $actualizationService): void
    {
        try {
            $actualization = Actualization::findOrFail($this->actualizationId);
            
            Log::info('Processing page actualization job started', [
                'actualization_id' => $this->actualizationId,
                'page_id' => $actualization->page_id,
                'attempt' => $this->attempts(),
            ]);

            $actualizationService->process($actualization);

            Log::info('Processing page actualization job completed', [
                'actualization_id' => $this->actualizationId,
                'page_id' => $actualization->page_id,
            ]);

        } catch (\Exception $e) {
            Log::error('Processing page actualization job failed', [
                'actualization_id' => $this->actualizationId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Если это последняя попытка, обновляем статус на failed
            if ($this->attempts() >= $this->tries) {
                try {
                    $actualization = Actualization::find($this->actualizationId);
                    if ($actualization) {
                        $actualization->update(['status' => Actualization::STATUS_FAILED]);
                    }
                } catch (\Exception $updateException) {
                    Log::error('Failed to update actualization status to failed', [
                        'actualization_id' => $this->actualizationId,
                        'error' => $updateException->getMessage(),
                    ]);
                }
            }

            throw $e;
        }
    }

    /**
     * Обработать неудачное выполнение задачи
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Page actualization job ultimately failed', [
            'actualization_id' => $this->actualizationId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        try {
            $actualization = Actualization::find($this->actualizationId);
            if ($actualization && $actualization->status !== Actualization::STATUS_FAILED) {
                $actualization->update(['status' => Actualization::STATUS_FAILED]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update actualization status in failed callback', [
                'actualization_id' => $this->actualizationId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Определить уникальный ID задачи
     */
    public function uniqueId(): string
    {
        return 'process_page_actualization_' . $this->actualizationId;
    }

    /**
     * Получить теги для мониторинга задачи
     */
    public function tags(): array
    {
        return [
            'actualization',
            'page_actualization',
            'actualization_id:' . $this->actualizationId,
        ];
    }
}
