<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GenerationModel;
use App\Models\Project;
use App\Models\ProjectGenerationModel;
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

        // Получаем модели с данными из pivot таблицы
        $response = $project->generationModels()
            ->withPivot('generation_type')
            ->get(['generation_models.id', 'generation_models.name'])
            ->map(function ($model) {
                return [
                    'name' => $model->name,
                    'generation_type' => $model->pivot->generation_type,
                ];
            })
            ->values();

        return response()->json($response);
    }
}
