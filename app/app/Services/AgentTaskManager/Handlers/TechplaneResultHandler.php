<?php

namespace App\Services\AgentTaskManager\Handlers;

use App\Interfaces\DisplayableResource;
use App\Interfaces\LLM\AgentResultHandlerInterface;
use App\Models\AgentTask;
use App\Models\Techplane;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;

class TechplaneResultHandler implements AgentResultHandlerInterface
{
    const OPTION_TECHPLANE_ID = 'techplane_id';
    const PAYLOAD_CONTENT_FILED = 'content';

    public function __construct(private Techplane $techplane)
    {
    }

    public function getOptions(): array
    {
        return [
            self::OPTION_TECHPLANE_ID => $this->techplane->id,
        ];
    }

    public function handleResult(?string $result): void
    {
        if(!empty($result)){
            $this->techplane->content = $result;
        }

        $this->techplane->generation_status = Techplane::STATUS_COMPLETED;

        $this->techplane->save();

        Log::info('Techplane generation completed', [
            'techplane_id' => $this->techplane->id,
            'task_id' => $this->techplane->task_id
        ]);
    }

    public static function createFromTask(AgentTask $task): static
    {
        $techplaneId = $task->handler_options[self::OPTION_TECHPLANE_ID] ?? null;

        if(is_null($techplaneId)) {
            throw new Exception('AgentTask have no required option', 500);
        }

        $techplane = Techplane::findOrFail($techplaneId);

        return new static($techplane);
    }

    public function getTargetResource(): ?DisplayableResource
    {
        return $this->techplane;
    }
}
