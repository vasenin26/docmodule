<?php

namespace App\Common\DTO;

class DraftApprovalResultDTO
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly bool $taskCreated = false,
        public readonly ?int $taskId = null,
        public readonly ?string $taskError = null
    ) {}

    public static function success(string $message): self
    {
        return new self(
            success: true,
            message: $message
        );
    }

    public static function withTask(string $message, int $taskId): self
    {
        return new self(
            success: true,
            message: $message,
            taskCreated: true,
            taskId: $taskId
        );
    }

    public static function withTaskError(string $message, string $error): self
    {
        return new self(
            success: true,
            message: $message,
            taskError: $error
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'task_created' => $this->taskCreated,
            'task_id' => $this->taskId,
            'task_error' => $this->taskError,
        ];
    }
}
