<?php

namespace App\Services\AgentTaskManager\Handlers;

use App\Interfaces\DisplayableResource;
use App\Interfaces\HtmlToMdInterface;
use App\Interfaces\LLM\AgentResultHandlerInterface;
use App\Models\AgentTask;
use App\Models\Actualization;
use App\Models\Patch;
use Illuminate\Support\Facades\Log;
use Exception;

class ActualizationResultHandler implements AgentResultHandlerInterface
{
    const OPTION_ACTUALIZATION_ID = 'actualization_id';

    public function __construct(
        private Actualization $actualization,
        private HtmlToMdInterface $converter,
    )
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

            if(json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception(json_last_error_msg());
            }

            if(!is_array($data) || empty($data['title']) || empty($data['content'])) {
                throw new Exception('Wrong data format');
            }

            Patch::create([
                'target' => get_class($this->actualization),
                'target_id' => $this->actualization->id,
                'title' => $data['title'],
                'content' => $data['content'],
            ]);
        }

        Log::info('Actualization completed', [
            'actualization_id' => $this->actualization->id,
            'page_id' => $this->actualization->page_id,
            'page_version_id' => $this->actualization->page_version_id
        ]);

        $this->actualization->update(['status' => Actualization::STATUS_COMPLETED]);
    }

    public static function createFromTask(AgentTask $task): static
    {
        $actualizationId = $task->handler_options[self::OPTION_ACTUALIZATION_ID] ?? null;

        if(is_null($actualizationId)) {
            throw new Exception('AgentTask have no required option', 500);
        }

        $actualization = Actualization::findOrFail($actualizationId);

        return new static($actualization, app()->get(HtmlToMdInterface::class));
    }

    public function getTargetResource(): ?DisplayableResource
    {
        return $this->actualization;
    }
}
