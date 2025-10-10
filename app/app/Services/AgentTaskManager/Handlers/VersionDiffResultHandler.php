<?php

namespace App\Services\AgentTaskManager\Handlers;

use App\Interfaces\LLM\AgentResultHandlerInterface;
use App\Models\AgentTask;
use App\Models\VersionDiffTask;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;

class VersionDiffResultHandler implements AgentResultHandlerInterface
{
    const OPTION_VERSION_DIFF_TASK_ID = 'version_diff_task_id';

    public function __construct(private VersionDiffTask $versionDiffTask)
    {
    }

    public function getOptions(): array
    {
        return [
            self::OPTION_VERSION_DIFF_TASK_ID => $this->versionDiffTask->id,
        ];
    }

    public function handleResult(?string $result): void
    {
        if(!empty($result)) {
            $this->versionDiffTask->content = $result;
        }

        $this->versionDiffTask->generation_status = VersionDiffTask::STATUS_COMPLETED;

        $this->versionDiffTask->save();
    }

    public static function createFromTask(AgentTask $task): static
    {
        $diffId = $task->handler_options[self::OPTION_VERSION_DIFF_TASK_ID] ?? null;

        if(is_null($diffId)) {
            throw new Exception('AgentTask have no required option', 500);
        }

        $versionDiffTask = VersionDiffTask::findOrFail($diffId);

        return new static($versionDiffTask);
    }
}
