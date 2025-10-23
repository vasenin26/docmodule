<?php

namespace Tests\Unit;

use App\Models\AgentTask;
use App\Http\Controllers\DashboardController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testGetTotalCostsReturnsZeroWhenNoAgentTasks(): void
    {
        // Убедимся, что таблица пустая
        AgentTask::query()->truncate();

        $controller = new DashboardController();

        $this->assertSame(0, $controller->getTotalCosts());
    }

    public function testGetTotalCostsSumsCosts(): void
    {
        // Создаем пару задач с указанной стоимостью (в формате RUB * 1000)
        AgentTask::factory()->create(['cost' => 100]);
        AgentTask::factory()->create(['cost' => 250]);

        $controller = new DashboardController();

        $this->assertSame(350, $controller->getTotalCosts());
    }
}
