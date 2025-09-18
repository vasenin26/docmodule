<?php

namespace App\Common\DTO;

use App\Models\SolutionMergeRequest;
use App\Models\Techplane;

class TechplaneApiDTO
{
    public function __construct(
        public int $id,
        public string $status,
        public string $generationStatus,
        public array $solutions,
    ) {}

    public static function fromModel(Techplane $model): self
    {
        $model->loadMissing(['solutions.mergeRequests']);

        $solutions = $model->solutions->map(function ($solution) {
            $mergeRequests = $solution->mergeRequests->map(function (SolutionMergeRequest $mr) {
                return [
                    'id' => $mr->id,
                    'url' => $mr->url,
                    'created_by' => $mr->created_by,
                    'created_at' => $mr->created_at?->toISOString(),
                ];
            })->values()->all();

            return [
                'id' => $solution->id,
                'content' => $solution->content,
                'mergeRequests' => $mergeRequests,
            ];
        })->values()->all();

        return new self(
            id: $model->id,
            status: (string) $model->status,
            generationStatus: (string) $model->generation_status,
            solutions: $solutions,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'generation_status' => $this->generationStatus,
            'solutions' => $this->solutions,
        ];
    }
}


