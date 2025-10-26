<?php

namespace App\Http\Controllers;

use App\Common\Enums\AgentTaskType;
use App\Http\Requests\ExpenseSummaryRequest;
use App\Http\Resources\ExpenseSummaryResource;
use App\Services\ExpenseSummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseSummaryController extends Controller
{
    public function __construct(
        private readonly ExpenseSummaryService $expenseSummaryService
    ) {}

    /**
     * Отображение страницы сводки расходов
     */
    public function index(Request $request): Response
    {
        $userId = Auth::id();
        
        // Получаем проекты пользователя для фильтра
        $projects = $this->expenseSummaryService->getUserProjects($userId);

        // Получаем список используемых моделей агентов для проектов пользователя
        $models = $this->expenseSummaryService->getUsedModels($userId);
        
        return Inertia::render('ExpenseSummary', [
            'projects' => $projects,
            'taskTypes' => collect(AgentTaskType::cases())->map(function (AgentTaskType $type) {
                return [
                    'value' => $type->value,
                    'label' => $type->getDescription()
                ];
            })->toArray(),
            'models' => $models
        ]);
    }

    /**
     * API endpoint для получения данных сводки
     */
    public function getData(ExpenseSummaryRequest $request): JsonResponse
    {
        $userId = Auth::id();
        
        // Read optional model filter from validated data (client sends 'model' param)
        $model = $request->validated('model');

        $data = $this->expenseSummaryService->getExpenseSummary(
            userId: $userId,
            period: $request->validated('period', 'day'),
            dateFrom: $request->validated('date_from') ? 
                \Carbon\Carbon::parse($request->validated('date_from')) : null,
            dateTo: $request->validated('date_to') ? 
                \Carbon\Carbon::parse($request->validated('date_to')) : null,
            taskType: $request->validated('task_type'),
            projectId: $request->validated('project_id'),
            modelValue: $model
        );

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
