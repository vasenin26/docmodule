<?php

namespace App\Services\AgentTaskManager\Handlers;

use App\Interfaces\LLM\AgentResultHandlerInterface;
use App\Models\AgentTask;
use App\Models\VersionDiffTask;
use Mockery\Exception;

class VersionDiffResultHandler implements AgentResultHandlerInterface
{
    const OPTION_VERSION_DIFF_TASK_ID = 'version_diff_task_id';

    public function __construct(private VersionDiffTask $versionDiffTask)
    {
    }

    public function getKey(): string
    {
        return 'versionDiffResultHandler';
    }

    public function getOptions(): string
    {
        return json_encode([
            self::OPTION_VERSION_DIFF_TASK_ID => $this->versionDiffTask->id,
        ]);
    }

    public function handleResult(string $result): void
    {
        $this->versionDiffTask->content = $result;
        $this->versionDiffTask->save();
    }

    public static function createFromTask(AgentTask $task): static
    {
        $diffId = $task->handler_options[self::OPTION_VERSION_DIFF_TASK_ID] ?? null;

        if(is_null($diffId)) {
            throw new Exception('Some shot error', 666);
        }

        $versionDiffTask = VersionDiffTask::findOrFail($diffId);

        return new static($versionDiffTask);
    }
}
