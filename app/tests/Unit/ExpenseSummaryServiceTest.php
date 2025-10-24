<?php

namespace Tests\Unit;

use App\Models\AgentTask;
use App\Models\Project;
use App\Models\User;
use App\Services\ExpenseSummaryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseSummaryServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExpenseSummaryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExpenseSummaryService();
    }

    public function test_get_expense_summary_returns_formatted_data()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        
        AgentTask::factory()->create([
            'project_id' => $project->id,
            'cost' => 1500, // 1.5 рубля
            'updated_at' => now()
        ]);

        $result = $this->service->getExpenseSummary($user->id, 'day');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(1.5, $result[0]->totalCost);
        $this->assertEquals(1, $result[0]->taskCount);
    }

    public function test_get_expense_summary_filters_by_date_range()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        
        $today = now();
        $yesterday = $today->copy()->subDay();
        
        AgentTask::factory()->create([
            'project_id' => $project->id,
            'cost' => 1000,
            'created_at' => $today
        ]);
        
        AgentTask::factory()->create([
            'project_id' => $project->id,
            'cost' => 2000,
            'created_at' => $yesterday
        ]);

        $result = $this->service->getExpenseSummary(
            $user->id,
            'day',
            $today->startOfDay()
        );

        $this->assertCount(1, $result);
        $this->assertEquals(1.0, $result[0]->totalCost);
    }
}
