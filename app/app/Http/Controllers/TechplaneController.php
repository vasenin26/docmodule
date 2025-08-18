<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateTechplaneJob;
use App\Models\Techplane;
use App\Interfaces\DocumentationControlInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class TechplaneController extends Controller
{
    public function __construct(
        protected DocumentationControlInterface $documentationControl
    ) {}
    public function show(Techplane $techplane): Response
    {
        $techplane->load(['task.page']);
        $pageAggregate = $this->documentationControl->getCurrentPageAggregate($techplane->task->page);
        $techplane->task->page = $pageAggregate->toArray();

        return Inertia::render('techplane/Show', [
            'techplane' => $techplane,
        ]);
    }

    /**
     * Restart generation of techplane.
     */
    public function restartGeneration(Techplane $techplane): JsonResponse
    {
        if ($techplane->generation_status === Techplane::STATUS_GENERATING) {
            return response()->json([
                'success' => false,
                'message' => 'Генерация уже выполняется'
            ], 400);
        }

        $techplane->update([
            'generation_status' => Techplane::STATUS_PENDING,
            'content' => null,
            'chat_id' => null
        ]);

        GenerateTechplaneJob::dispatch($techplane->id);

        return response()->json([
            'success' => true,
            'message' => 'Генерация перезапущена'
        ]);
    }

    /**
     * Check the generation status of a techplane.
     */
    public function checkGenerationStatus(Techplane $techplane): JsonResponse
    {
        return response()->json([
            'status' => $techplane->generation_status,
            'content' => $techplane->content,
            'updated_at' => $techplane->updated_at,
        ]);
    }
}
