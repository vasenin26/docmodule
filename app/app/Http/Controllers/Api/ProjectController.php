<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GenerationModel;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Возвращает список моделей генерации для проекта вместе с типом генерации из pivot
     *
     * @param Project $project
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generationModels(Project $project, Request $request)
    {
        // Агент устанавливается middleware AgentJwtAuth и доступен в request как 'agent'
        $agent = $request->agent ?? null;

        if (!$project->canAgentAccess($agent)) {
            abort(403);
        }

        // Получаем модели, связанные с проектом через таблицу project_generation_models
        $models = GenerationModel::whereHas('projectMappings', function ($q) use ($project) {
            $q->where('project_id', $project->id);
        })->get(['id','name']);

        // Формируем ответ: [{ name, generation_type }]
        $response = $models->map(function (GenerationModel $m) use ($project) {
            $pivot = $m->projectMappings()->where('project_id', $project->id)->first();
            return [
                'name' => $m->name,
                'generation_type' => $pivot ? $pivot->generation_type : null,
            ];
        })->values();

        return response()->json($response);
    }
}
