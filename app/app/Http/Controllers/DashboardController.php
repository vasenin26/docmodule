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

        $costStatistics = $this->getCostStatistics();

        return Inertia::render('Dashboard', [
            'token_statistics' => $tokenStatistics,
            'projects' => $projects,
            'cost_statistics' => $costStatistics,
        ]);
    }

    /**
     * Получение статистики токенов
     * Токены считаются только по проектам пользователя
     *
     * @return array
     */
    public function getTokenStatistics(): array
    {
        $userId = Auth::id();
        
        // Получаем ID проектов пользователя
        $userProjectIds = Project::where('owner_id', $userId)->pluck('id');

        // Если у пользователя нет проектов, возвращаем нули
        if ($userProjectIds->isEmpty()) {
            return [
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
            ];
        }

        // Получаем агрегированную статистику из agent_tasks по проектам пользователя
        $stats = AgentTask::whereIn('project_id', $userProjectIds)
            ->selectRaw('
                COALESCE(SUM(prompt_tokens), 0) as prompt_tokens,
                COALESCE(SUM(completion_tokens), 0) as completion_tokens,
                COALESCE(SUM(total_tokens), 0) as total_tokens
            ')->first();

        return [
            'prompt_tokens' => (int) $stats->prompt_tokens,
            'completion_tokens' => (int) $stats->completion_tokens,
            'total_tokens' => (int) $stats->total_tokens,
        ];
    }

    /**
     * Получение статистики расходов (общие, за день, за месяц)
     * Расходы считаются только по проектам пользователя
     *
     * @return array
     */
    public function getCostStatistics(): array
    {
        $userId = Auth::id();
        $now = now();
        $startOfDay = $now->copy()->startOfDay();
        $startOfMonth = $now->copy()->startOfMonth();

        // Получаем ID проектов пользователя
        $userProjectIds = Project::where('owner_id', $userId)->pluck('id');

        // Если у пользователя нет проектов, возвращаем нули
        if ($userProjectIds->isEmpty()) {
            return [
                'total' => 0,
                'daily' => 0,
                'monthly' => 0,
            ];
        }

        // Общие расходы по проектам пользователя
        $totalCost = AgentTask::whereIn('project_id', $userProjectIds)
            ->selectRaw('COALESCE(SUM(cost), 0) as total_cost')
            ->first();
        
        // Расходы за день по проектам пользователя
        $dailyCost = AgentTask::whereIn('project_id', $userProjectIds)
            ->where('updated_at', '>=', $startOfDay)
            ->selectRaw('COALESCE(SUM(cost), 0) as daily_cost')
            ->first();
            
        // Расходы за месяц по проектам пользователя
        $monthlyCost = AgentTask::whereIn('project_id', $userProjectIds)
            ->where('updated_at', '>=', $startOfMonth)
            ->selectRaw('COALESCE(SUM(cost), 0) as monthly_cost')
            ->first();

        return [
            'total' => (int) ($totalCost->total_cost ?? 0),
            'daily' => (int) ($dailyCost->daily_cost ?? 0),
            'monthly' => (int) ($monthlyCost->monthly_cost ?? 0),
        ];
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
