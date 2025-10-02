<?php

namespace App\Http\Controllers\Api;

use App\Common\DTO\Techplane\TechplaneApiDTO;
use App\Common\DTO\Techplane\TechplaneMarkDoneDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Techplane\MarkDoneRequest;
use App\Models\Techplane;
use App\Services\TechplaneService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class TechplaneController extends Controller
{
    public function __construct(private readonly TechplaneService $service)
    {
    }

    public function markDone(MarkDoneRequest $request, Techplane $techplane): JsonResponse
    {
        if ($techplane->status === Techplane::EXECUTION_EXECUTED) {
            // Still allow adding MR later per acceptance criteria; do not 409 here.
            // We'll proceed to add MR and keep executed status.
        }

        if ($techplane->generation_status !== Techplane::STATUS_COMPLETED) {
            throw new ConflictHttpException('Techplane generation is not completed.');
        }

        $dto = TechplaneMarkDoneDTO::fromRequest($request, $techplane);
        $updated = $this->service->markDone($dto);

        return response()->json(TechplaneApiDTO::fromModel($updated)->toArray());
    }
}
