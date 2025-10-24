<?php

namespace Tests\Feature;

use App\Models\AgentTask;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_summary_api_returns_data()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        
        AgentTask::factory()->create([
            'project_id' => $project->id,
            'cost' => 1000, // 1 рубль в формате RUB*1000
            'type' => 'text'
        ]);

        $response = $this->actingAs($user)
            ->getJson('/expense-summary/data?period=day');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'period',
                        'totalCost',
                        'taskCount',
                        'periodLabel'
                    ]
                ]
            ]);
    }

    public function test_expense_summary_filters_by_project()
    {
        $user = User::factory()->create();
        $project1 = Project::factory()->create(['owner_id' => $user->id]);
        $project2 = Project::factory()->create(['owner_id' => $user->id]);
        
        AgentTask::factory()->create([
            'project_id' => $project1->id,
            'cost' => 1000
        ]);
        
        AgentTask::factory()->create([
            'project_id' => $project2->id,
            'cost' => 2000
        ]);

        $response = $this->actingAs($user)
            ->getJson("/expense-summary/data?period=day&project_id={$project1->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertCount(1, $data);
        $this->assertEquals(1.0, $data[0]['totalCost']); // 1000 / 1000 = 1.0
    }

    public function test_expense_summary_requires_authentication()
    {
        $response = $this->getJson('/expense-summary/data?period=day');
        $response->assertStatus(401);
    }

    public function test_expense_summary_validates_period_parameter()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->getJson('/expense-summary/data?period=invalid');
        
        $response->assertStatus(422);
    }

    public function test_expense_summary_filters_by_date_range()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        
        $today = now();
        $yesterday = $today->copy()->subDay();
        
        AgentTask::factory()->create([
            'project_id' => $project->id,
            'cost' => 1000,
            'updated_at' => $today->startOfDay()
        ]);
        
        AgentTask::factory()->create([
            'project_id' => $project->id,
            'cost' => 2000,
            'updated_at' => $yesterday
        ]);

        $response = $this->actingAs($user)
            ->getJson("/expense-summary/data?period=day&date_from={$today->format('Y-m-d')}&date_to={$today->format('Y-m-d')}");

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertCount(1, $data);
        $this->assertEquals(1.0, $data[0]['totalCost']); // 1000 / 1000 = 1.0
    }

    public function test_expense_summary_filters_by_task_type()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        
        AgentTask::factory()->create([
            'project_id' => $project->id,
            'cost' => 1000,
            'type' => 'text'
        ]);
        
        AgentTask::factory()->create([
            'project_id' => $project->id,
            'cost' => 2000,
            'type' => 'code'
        ]);

        $response = $this->actingAs($user)
            ->getJson('/expense-summary/data?period=day&task_type=text');

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertCount(1, $data);
        $this->assertEquals(1.0, $data[0]['totalCost']); // 1000 / 1000 = 1.0
    }

    public function test_expense_summary_returns_empty_data_for_user_without_projects()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->getJson('/expense-summary/data?period=day');

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertEmpty($data);
    }
}
