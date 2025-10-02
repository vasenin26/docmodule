<?php

namespace App\Common\DTO\Techplane;

use App\Common\DTO\GeneratorContextDTO;

readonly class TechplaneDataDTO
{
    public function __construct(
        public string $taskDescription,
        public GeneratorContextDTO $context,
        public ?int $techplaneId = null,
    ) {}

    public function toArray(): array
    {
        return [
            'task_description' => $this->taskDescription,
            'context' => $this->context->toArray(),
            'techplane_id' => $this->techplaneId,
        ];
    }
}
