<?php

namespace App\Http\Controllers;

use App\Common\Enums\AgentTaskType;
use App\Http\Requests\StoreGenerationModelRequest;
use App\Models\GenerationModel;
use App\Models\Project;
use App\Models\ProjectGenerationModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProjectGenerationModelController extends Controller
{
    public function index(Project $project): Response
    {
        if ($project->owner_id !== Auth::id()) {
            abort(403);
        }

        $models = GenerationModel::query()->orderBy('price_out')->get(['id','name','context_size','price_in','price_out']);
        $types = collect(AgentTaskType::cases())->map(fn($t) => [
            'value' => $t->value,
            'label' => $t->getDescription(),
        ]);

        $mappings = ProjectGenerationModel::query()
            ->where('project_id', $project->id)
            ->get(['generation_type','model_id']);

        return Inertia::render('projects/GenerationModels', [
            'project' => $project,
            'generation_types' => $types,
            'models' => $models,
            'mappings' => $mappings,
        ]);
    }

    public function show(Project $project, string $type): JsonResponse
    {
        if ($project->owner_id !== Auth::id()) {
            abort(403);
        }

        $mapping = ProjectGenerationModel::query()
            ->where('project_id', $project->id)
            ->where('generation_type', $type)
            ->first();

        return response()->json([
            'generation_type' => $type,
            'model_id' => $mapping?->model_id,
        ]);
    }

    public function store(StoreGenerationModelRequest $request, Project $project): JsonResponse
    {
        if ($project->owner_id !== Auth::id()) {
            abort(403);
        }

        $data = [
            'project_id' => $project->id,
            'generation_type' => $request->input('generation_type'),
        ];

        $mapping = ProjectGenerationModel::updateOrCreate(
            $data,
            ['model_id' => $request->input('model_id')]
        );

        return response()->json([
            'success' => true,
            'mapping' => [
                'generation_type' => $mapping->generation_type,
                'model_id' => $mapping->model_id,
            ],
        ]);
    }

    public function destroy(Project $project, string $type): JsonResponse
    {
        if ($project->owner_id !== Auth::id()) {
            abort(403);
        }

        ProjectGenerationModel::query()
            ->where('project_id', $project->id)
            ->where('generation_type', $type)
            ->delete();

        return response()->json(['success' => true]);
    }
}


