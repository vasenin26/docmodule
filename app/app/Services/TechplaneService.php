<?php

namespace App\Services;

use App\Common\DTO\Techplane\TechplaneMarkDoneDTO;
use App\Models\Solution;
use App\Models\SolutionMergeRequest;
use App\Models\Techplane;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class TechplaneService
{
    public function markDone(TechplaneMarkDoneDTO $dto): Techplane
    {
        /** @var Techplane $techplane */
        $techplane = Techplane::query()->findOrFail($dto->techplaneId);

        if ($techplane->generation_status !== Techplane::STATUS_COMPLETED) {
            throw new ConflictHttpException('Techplane generation is not completed.');
        }

        return DB::transaction(function () use ($dto, $techplane) {
            // Reuse existing solution or create a new one if none
            /** @var Solution|null $solution */
            $solution = $techplane->solutions()->latest('solutions.id')->first();
            if (!$solution) {
                $solution = Solution::query()->create();
                // attach relation if not exists
                $techplane->solutions()->syncWithoutDetaching([$solution->id]);
            }

            // Create merge request record
            SolutionMergeRequest::query()->create([
                'solution_id' => $solution->id,
                'url' => $dto->mergeRequestUrl,
                'created_by' => $dto->userId,
            ]);

            // Idempotently mark executed
            if ($techplane->status !== Techplane::EXECUTION_EXECUTED) {
                $techplane->status = Techplane::EXECUTION_EXECUTED;
                $techplane->save();
            }

            return $techplane->load(['solutions.mergeRequests']);
        });
    }
}
