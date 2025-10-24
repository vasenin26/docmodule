<?php

namespace App\Http\Resources;

use App\Common\DTO\ExpenseSummaryDTO;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseSummaryResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var ExpenseSummaryDTO $dto */
        $dto = $this->resource;
        
        return [
            'period' => $dto->period,
            'total_cost' => $dto->totalCost,
            'task_count' => $dto->taskCount,
            'period_label' => $dto->periodLabel,
            'project_name' => $dto->projectName,
        ];
    }
}
