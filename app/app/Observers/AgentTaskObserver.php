<?php

namespace App\Observers;

use App\Jobs\SendAgentTaskToOrchestratorJob;
use App\Models\AgentTask;
use Illuminate\Support\Facades\Log;

class AgentTaskObserver
{
    /**
     * Обработчик события создания AgentTask
     * 
     * @param AgentTask $task Созданная задача агента
     */
    public function created(AgentTask $task): void
    {
        Log::info('AgentTaskObserver: Task created, dispatching orchestrator job', [
            'task_id' => $task->id,
            'type' => $task->type->value,
            'project_id' => $task->project_id,
            'chat_id' => $task->chat_id,
        ]);

        // Диспатчим Job в очередь с минимальным контекстом (только ID)
        SendAgentTaskToOrchestratorJob::dispatch($task->id);
    }
}

