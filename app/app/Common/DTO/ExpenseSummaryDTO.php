<?php

namespace App\Common\DTO;

readonly class ExpenseSummaryDTO
{
    public function __construct(
        public string $period,
        public float $totalCost,
        public int $taskCount,
        public string $periodLabel,
        public ?string $projectName = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            period: $data['period'],
            totalCost: (float) $data['total_cost'],
            taskCount: (int) $data['task_count'],
            periodLabel: $data['period_label'],
            projectName: $data['project_name'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'period' => $this->period,
            'total_cost' => $this->totalCost,
            'task_count' => $this->taskCount,
            'period_label' => $this->periodLabel,
            'project_name' => $this->projectName,
        ];
    }
}
