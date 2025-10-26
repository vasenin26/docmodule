<?php

namespace App\Services;

use App\Common\DTO\ExpenseSummaryDTO;
use App\Common\Enums\AgentTaskType;
use App\Models\AgentTask;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ExpenseSummaryService
{
    /**
     * Получить агрегированные данные расходов
     */
    public function getExpenseSummary(
        int $userId,
        string $period = 'day',
        ?Carbon $dateFrom = null,
        ?Carbon $dateTo = null,
        ?string $taskType = null,
        ?int $projectId = null,
        ?string $modelValue = null
    ): array {
        // Получаем ID проектов пользователя
        $userProjectIds = $this->getUserProjectIds($userId);
        
        if (empty($userProjectIds)) {
            return [];
        }

        $query = AgentTask::whereIn('project_id', $userProjectIds)
            ->whereNotNull('cost');

        // Фильтрация по датам
        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom->startOfDay());
        }
        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo->endOfDay());
        }

        // Фильтрация по типу задач
        if ($taskType && AgentTaskType::isValid($taskType)) {
            $query->where('type', $taskType);
        }

        // Фильтрация по конкретному проекту
        if ($projectId && in_array($projectId, $userProjectIds)) {
            $query->where('project_id', $projectId);
        }

        // Фильтрация по модели агента (agent_model)
        if ($modelValue !== null && $modelValue !== '') {
            $query->where('agent_model', $modelValue);
        }

        // Группировка по периодам
        $groupByClause = $this->getGroupByClause($period);
        
        $results = $query
            ->selectRaw("
                {$groupByClause} as period,
                ROUND(SUM(cost)::numeric / 1000, 2) as total_cost,
                COUNT(*) as task_count
            ")
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return $this->formatResults($results, $period);
    }

    /**
     * Получить список проектов пользователя
     */
    public function getUserProjects(int $userId): Collection
    {
        return Project::where('owner_id', $userId)
            ->select('id', 'title')
            ->orderBy('title')
            ->get();
    }

    /**
     * Получить ID проектов пользователя
     */
    private function getUserProjectIds(int $userId): array
    {
        return Project::where('owner_id', $userId)->pluck('id')->toArray();
    }

    /**
     * Получить SQL для группировки по периодам (PostgreSQL)
     */
    private function getGroupByClause(string $period): string
    {
        return match($period) {
            'day' => 'DATE(created_at)',
            'week' => 'DATE_TRUNC(\'week\', created_at)',
            'month' => 'DATE_TRUNC(\'month\', created_at)',
            default => 'DATE(created_at)'
        };
    }

    /**
     * Форматировать результаты
     */
    private function formatResults(Collection $results, string $period): array
    {
        return $results->map(function ($item) use ($period) {
            $periodLabel = $this->formatPeriodLabel($item->period, $period);
            
            return ExpenseSummaryDTO::fromArray([
                'period' => $item->period,
                'total_cost' => (float) $item->total_cost,
                'task_count' => (int) $item->task_count,
                'period_label' => $periodLabel,
            ]);
        })->toArray();
    }

    /**
     * Форматировать метку периода
     */
    private function formatPeriodLabel(string $period, string $periodType): string
    {
        $date = Carbon::parse($period);
        
        return match($periodType) {
            'day' => $date->format('d.m.Y'),
            'week' => $date->format('d.m.Y') . ' - ' . $date->addDays(6)->format('d.m.Y'),
            'month' => $date->format('F Y'),
            default => $date->format('d.m.Y')
        };
    }

    /**
     * Получить уникальные используемые модели агентов для проектов пользователя
     */
    public function getUsedModels(int $userId): array
    {
        $userProjectIds = $this->getUserProjectIds($userId);

        if (empty($userProjectIds)) {
            return [];
        }

        return AgentTask::whereIn('project_id', $userProjectIds)
            ->whereNotNull('agent_model')
            ->distinct()
            ->orderBy('agent_model')
            ->pluck('agent_model')
            ->filter()
            ->toArray();
    }
}
