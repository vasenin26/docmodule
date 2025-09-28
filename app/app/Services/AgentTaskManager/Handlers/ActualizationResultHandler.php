<?php

namespace App\Services\AgentTaskManager\Handlers;

use App\Interfaces\LLM\AgentResultHandlerInterface;
use App\Models\AgentTask;
use App\Models\Actualization;
use Illuminate\Support\Facades\Log;
use Exception;

class ActualizationResultHandler implements AgentResultHandlerInterface
{
    const OPTION_ACTUALIZATION_ID = 'actualization_id';

    public function __construct(private Actualization $actualization)
    {
    }

    public function getOptions(): array
    {
        return [
            self::OPTION_ACTUALIZATION_ID => $this->actualization->id,
        ];
    }

    public function handleResult(?string $result): void
    {
        if($result !== null) {
            // Обновляем черновик с новым содержимым
            $draft = $this->actualization->pageVersion;
            $draft->update(['content' => $result]);

            // Обновляем статус актуализации
            $this->actualization->update(['status' => Actualization::STATUS_COMPLETED]);

            Log::info('Actualization completed', [
                'actualization_id' => $this->actualization->id,
                'page_id' => $this->actualization->page_id,
                'page_version_id' => $this->actualization->page_version_id
            ]);
        }
    }

    public static function createFromTask(AgentTask $task): static
    {
        $actualizationId = $task->handler_options[self::OPTION_ACTUALIZATION_ID] ?? null;

        if(is_null($actualizationId)) {
            throw new Exception('AgentTask have no required option', 500);
        }

        $actualization = Actualization::findOrFail($actualizationId);

        return new static($actualization);
    }
}
