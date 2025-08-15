<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateTechplaneJob;
use App\Models\Techplane;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class TechplaneController extends Controller
{
    public function show(Techplane $techplane): Response
    {
        $techplane->load(['task.page.creator', 'creator', 'llmChat']);

        return Inertia::render('techplane/Show', [
            'techplane' => [
                'id' => $techplane->id,
                'content' => $techplane->content,
                'generation_status' => $techplane->generation_status,
                'created_at' => $techplane->created_at,
                'updated_at' => $techplane->updated_at,
                'task' => [
                    'id' => $techplane->task->id,
                    'content' => $techplane->task->content,
                    'page' => [
                        'id' => $techplane->task->page->id,
                        'title' => $techplane->task->page->title,
                    ],
                ],
                'creator' => [
                    'id' => $techplane->creator->id,
                    'name' => $techplane->creator->name,
                    'email' => $techplane->creator->email,
                ],
                'llm_chat' => $techplane->llmChat ? [
                    'id' => $techplane->llmChat->id,
                    'messages' => $techplane->llmChat->messages,
                ] : null,
            ]
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
