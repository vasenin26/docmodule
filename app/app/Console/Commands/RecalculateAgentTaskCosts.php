<?php

namespace App\Console\Commands;

use App\Models\AgentTask;
use App\Services\Pricing\PricingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateAgentTaskCosts extends Command
{
    protected $signature = 'agent-tasks:recalculate-costs {--dry-run : Show what would be updated without making changes}';
    protected $description = 'Recalculate cost for all AgentTasks where cost is null';

    public function handle()
    {
        $pricingService = new PricingService();
        $dryRun = $this->option('dry-run');

        $this->info('Starting cost recalculation for AgentTasks...');

        // Находим все задачи с cost = null, у которых есть токены и модель
        $tasks = AgentTask::whereNull('cost')
            ->whereNotNull('agent_model')
            ->where(function ($query) {
                $query->whereNotNull('prompt_tokens')
                    ->orWhereNotNull('completion_tokens');
            })
            ->get();

        if ($tasks->isEmpty()) {
            $this->info('No tasks found that need cost recalculation.');
            return;
        }

        $this->info("Found {$tasks->count()} tasks to process.");

        $updatedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($tasks as $task) {
            try {
                $promptTokens = (int) ($task->prompt_tokens ?? 0);
                $completionTokens = (int) ($task->completion_tokens ?? 0);

                // Пропускаем задачи без токенов
                if ($promptTokens === 0 && $completionTokens === 0) {
                    $skippedCount++;
                    continue;
                }

                $calculatedCost = $pricingService->calculateCost(
                    $task->agent_model,
                    $promptTokens,
                    $completionTokens
                );

                if ($calculatedCost !== null) {
                    if (!$dryRun) {
                        $task->update(['cost' => $calculatedCost]);
                    }
                    
                    $this->line("Task {$task->id}: model={$task->agent_model}, tokens={$promptTokens}+{$completionTokens}, cost={$calculatedCost}");
                    $updatedCount++;
                } else {
                    $this->warn("Task {$task->id}: Could not calculate cost (model: {$task->agent_model})");
                    $skippedCount++;
                }
            } catch (\Exception $e) {
                $this->error("Task {$task->id}: Error - {$e->getMessage()}");
                $errorCount++;
            }
        }

        $this->newLine();
        $this->info('Recalculation completed!');
        $this->info("Updated: {$updatedCount}");
        $this->info("Skipped: {$skippedCount}");
        $this->info("Errors: {$errorCount}");

        if ($dryRun) {
            $this->warn('This was a dry run. No changes were made.');
        }
    }
}
