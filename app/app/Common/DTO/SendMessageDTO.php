<?php

namespace App\Common\DTO;

use App\Http\Requests\SendTaskMessageRequest;
use App\Models\VersionDiffTask;

class SendMessageDTO
{
    public function __construct(
        public readonly int $taskId,
        public readonly string $message,
        public readonly int $userId
    ) {}

    public static function fromRequest(SendTaskMessageRequest $request, VersionDiffTask $task): self
    {
        return new self(
            taskId: $task->id,
            message: $request->validated()['message'],
            userId: $request->user()->id
        );
    }
}
