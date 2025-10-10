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
    const PAYLOAD_CONTENT_FILED = 'content';

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
            $data = json_decode($result, true);

            if(is_array($data) && array_key_exists(self::PAYLOAD_CONTENT_FILED, $data)) {
                $draft = $this->actualization->pageVersion;
                $draft->update(['content' => $data[self::PAYLOAD_CONTENT_FILED]]);
            }

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
