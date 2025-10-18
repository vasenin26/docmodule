<?php

namespace App\Services\AgentTaskManager\Handlers;

use App\Common\Enums\GenerationStatus;
use App\Interfaces\DisplayableResource;
use App\Interfaces\LLM\AgentResultHandlerInterface;
use App\Models\AgentTask;
use App\Models\Implementation;
use Illuminate\Support\Facades\Log;
use Exception;

class ImplementationResultHandler implements AgentResultHandlerInterface
{
    const OPTION_IMPLEMENTATION_ID = 'implementation_id';

    public function __construct(private Implementation $implementation)
    {
    }

    public function getOptions(): array
    {
        return [
            self::OPTION_IMPLEMENTATION_ID => $this->implementation->id,
        ];
    }

    public function handleResult(?string $result): void
    {
        if ($result !== null) {
            $this->implementation->content = $result;
        }

        Log::info('Implementation completed', [
            'implementation_id' => $this->implementation->id,
            'techplane_id' => $this->implementation->techplane_id
        ]);

        $this->implementation->status = GenerationStatus::COMPLETED;
        $this->implementation->save();
    }

    public static function createFromTask(AgentTask $task): static
    {
        $implementationId = $task->handler_options[self::OPTION_IMPLEMENTATION_ID] ?? null;

        if (is_null($implementationId)) {
            throw new Exception('AgentTask have no required option', 500);
        }

        $implementation = Implementation::findOrFail($implementationId);

        return new static($implementation);
    }

    public function getTargetResource(): ?DisplayableResource
    {
        return $this->implementation;
    }
}
