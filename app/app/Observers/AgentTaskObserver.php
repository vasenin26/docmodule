<?php

namespace App\Observers;

use App\Models\AgentTask;
use App\Services\Pricing\PricingService;

class AgentTaskObserver
{
    public function __construct(
        private readonly PricingService $pricingService
    ) {}

    /**
     * Handle the AgentTask "updating" event.
     * Пересчитывает стоимость перед обновлением задачи
     */
    public function updating(AgentTask $agentTask): void
    {
        // Пересчитываем стоимость только если изменились токены или модель
        if ($this->shouldRecalculateCost($agentTask)) {
            $cost = $this->pricingService->calculateCost(
                $agentTask->agent_model,
                $agentTask->prompt_tokens ?? 0,
                $agentTask->completion_tokens ?? 0
            );
            
            $agentTask->cost = $cost;
        }
    }

    /**
     * Проверяет, нужно ли пересчитывать стоимость
     */
    private function shouldRecalculateCost(AgentTask $agentTask): bool
    {
        // Если модель не указана, не пересчитываем
        if (empty($agentTask->agent_model)) {
            return false;
        }

        // Пересчитываем если изменились токены или модель
        $tokensChanged = $agentTask->isDirty(['prompt_tokens', 'completion_tokens']);
        $modelChanged = $agentTask->isDirty('agent_model');
        
        return $tokensChanged || $modelChanged;
    }
}
