<?php

namespace Tests\Feature;

use App\Models\AgentTask;
use App\Models\GenerationModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecalculateAgentTaskCostsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_recalculates_costs_for_tasks_with_null_cost()
    {
        // Создаем модель с ценами
        $model = GenerationModel::factory()->priced(0.05, 0.08)->create([
            'name' => 'gpt-test',
        ]);

        // Создаем задачи с cost = null
        $task1 = AgentTask::factory()->create([
            'agent_model' => 'gpt-test',
            'prompt_tokens' => 100000,
            'completion_tokens' => 200000,
            'cost' => null,
        ]);

        $task2 = AgentTask::factory()->create([
            'agent_model' => 'gpt-test',
            'prompt_tokens' => 50000,
            'completion_tokens' => 100000,
            'cost' => null,
        ]);

        // Задача с уже установленным cost не должна измениться
        $task3 = AgentTask::factory()->create([
            'agent_model' => 'gpt-test',
            'prompt_tokens' => 100000,
            'completion_tokens' => 200000,
            'cost' => 1000, // уже установлен
        ]);

        $this->artisan('agent-tasks:recalculate-costs')
            ->expectsOutput('Starting cost recalculation for AgentTasks...')
            ->expectsOutput("Found 2 tasks to process.")
            ->assertExitCode(0);

        // Проверяем, что cost был пересчитан
        $task1->refresh();
        $task2->refresh();
        $task3->refresh();

        // Ожидаемый расчет: (100000/1000000) * 0.05 + (200000/1000000) * 0.08 = 0.005 + 0.016 = 0.021
        // В БД: 21 (0.021 * 1000)
        $this->assertEquals(21, $task1->cost);

        // Ожидаемый расчет: (50000/1000000) * 0.05 + (100000/1000000) * 0.08 = 0.0025 + 0.008 = 0.0105
        // В БД: 11 (0.0105 * 1000, округлено)
        $this->assertEquals(11, $task2->cost);

        // Задача с уже установленным cost не должна измениться
        $this->assertEquals(1000, $task3->cost);
    }

    public function test_skips_tasks_without_model_or_tokens()
    {
        // Задача без модели
        AgentTask::factory()->create([
            'agent_model' => null,
            'prompt_tokens' => 100000,
            'completion_tokens' => 200000,
            'cost' => null,
        ]);

        // Задача без токенов
        AgentTask::factory()->create([
            'agent_model' => 'gpt-test',
            'prompt_tokens' => null,
            'completion_tokens' => null,
            'cost' => null,
        ]);

        $this->artisan('agent-tasks:recalculate-costs')
            ->expectsOutput('Starting cost recalculation for AgentTasks...')
            ->expectsOutput('No tasks found that need cost recalculation.')
            ->assertExitCode(0);
    }

    public function test_dry_run_option()
    {
        $model = GenerationModel::factory()->priced(0.05, 0.08)->create([
            'name' => 'gpt-test',
        ]);

        $task = AgentTask::factory()->create([
            'agent_model' => 'gpt-test',
            'prompt_tokens' => 100000,
            'completion_tokens' => 200000,
            'cost' => null,
        ]);

        $this->artisan('agent-tasks:recalculate-costs --dry-run')
            ->expectsOutput('Starting cost recalculation for AgentTasks...')
            ->expectsOutput("Found 1 tasks to process.")
            ->expectsOutput('This was a dry run. No changes were made.')
            ->assertExitCode(0);

        // Проверяем, что cost не изменился
        $task->refresh();
        $this->assertNull($task->cost);
    }
}
