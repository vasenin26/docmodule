<?php

namespace App\Http\Controllers;

use App\Models\AgentTask;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Отображение страницы дашборда
     *
     * @return Response
     */
    public function index(): Response
    {
        $tokenStatistics = $this->getTokenStatistics();

        $projects = Project::where('owner_id', Auth::id())
            ->with(['owner'])
            ->orderBy('created_at', 'desc')
            ->get();

        $costStatistics = $this->getTotalCosts();

        return Inertia::render('Dashboard', [
            'token_statistics' => $tokenStatistics,
            'projects' => $projects,
            'cost_statistics' => $costStatistics,
        ]);
    }

    /**
     * Получение статистики токенов
     *
     * @return array
     */
    public function getTokenStatistics(): array
    {
        // Получаем агрегированную статистику из agent_tasks (новое место хранения токенов)
        $stats = AgentTask::selectRaw('\n            COALESCE(SUM(prompt_tokens), 0) as prompt_tokens,\n            COALESCE(SUM(completion_tokens), 0) as completion_tokens,\n            COALESCE(SUM(total_tokens), 0) as total_tokens\n        ')->first();

        return [
            'prompt_tokens' => (int) $stats->prompt_tokens,
            'completion_tokens' => (int) $stats->completion_tokens,
            'total_tokens' => (int) $stats->total_tokens,
        ];
    }

    /**
     * Получение общей суммы расходов (поле cost в agent_tasks)
     *
     * @return int
     */
    public function getTotalCosts(): int
    {
        // Используем COALESCE для обработки случая отсутствия записей или NULL
        $stats = AgentTask::selectRaw('COALESCE(SUM(cost), 0) as total_cost')->first();

        if (!$stats) {
            return 0;
        }

        // cost хранится как RUB * 1000 (миллибаблей) - приводим к целому
        return (int) $stats->total_cost;
    }

    /**
     * API endpoint для получения статистики токенов
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiTokenStatistics(Request $request)
    {
        $statistics = $this->getTokenStatistics();

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }
}
