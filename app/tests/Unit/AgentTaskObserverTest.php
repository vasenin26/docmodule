<?php

namespace Tests\Unit;

use App\Models\AgentTask;
use App\Models\GenerationModel;
use App\Models\LLMChat;
use App\Models\Project;
use App\Models\User;
use App\Observers\AgentTaskObserver;
use App\Services\Pricing\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentTaskObserverTest extends TestCase
{
    use RefreshDatabase;

    private PricingService $pricingService;
    private AgentTaskObserver $observer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->pricingService = new PricingService();
        $this->observer = new AgentTaskObserver($this->pricingService);
    }

    public function test_observer_recalculates_cost_when_tokens_change(): void
    {
        // Создаем модель с ценами
        $model = GenerationModel::create([
            'name' => 'test-model',
            'price_in' => 100.0,  // 100 рублей за 1M токенов
            'price_out' => 200.0  // 200 рублей за 1M токенов
        ]);

        // Создаем пользователя и проект
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        $chat = LLMChat::factory()->create();

        // Создаем задачу
        $task = AgentTask::create([
            'project_id' => $project->id,
            'type' => 'text',
            'status' => 'success',
            'agent_model' => 'test-model',
            'prompt_tokens' => 1000,
            'completion_tokens' => 500,
            'cost' => 0, // Изначально 0
            'created_by' => $user->id,
            'chat_id' => $chat->id
        ]);

        // Обновляем задачу - это должно вызвать обсервер
        $task->update([
            'prompt_tokens' => 2000,
            'completion_tokens' => 1000
        ]);

        // Проверяем, что стоимость пересчиталась
        $task->refresh();
        $this->assertNotNull($task->cost);
        $this->assertGreaterThan(0, $task->cost);
        
        // Проверяем конкретное значение (2000 токенов * 100 + 1000 токенов * 200) / 1000000 * 1000 = 400
        $this->assertEquals(400, $task->cost);
    }

    public function test_observer_recalculates_cost_when_model_changes(): void
    {
        // Создаем две модели с разными ценами
        $model1 = GenerationModel::create([
            'name' => 'cheap-model',
            'price_in' => 50.0,
            'price_out' => 100.0
        ]);

        $model2 = GenerationModel::create([
            'name' => 'expensive-model',
            'price_in' => 200.0,
            'price_out' => 400.0
        ]);

        // Создаем пользователя и проект
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        $chat = LLMChat::factory()->create();

        // Создаем задачу с первой моделью
        $task = AgentTask::create([
            'project_id' => $project->id,
            'type' => 'text',
            'status' => 'success',
            'agent_model' => 'cheap-model',
            'prompt_tokens' => 1000,
            'completion_tokens' => 500,
            'cost' => 100, // Изначальная стоимость
            'created_by' => $user->id,
            'chat_id' => $chat->id
        ]);

        // Меняем модель
        $task->update([
            'agent_model' => 'expensive-model'
        ]);

        // Проверяем, что стоимость пересчиталась (должна быть больше)
        $task->refresh();
        $this->assertNotNull($task->cost);
        $this->assertGreaterThan(100, $task->cost);
    }

    public function test_observer_does_not_recalculate_when_irrelevant_fields_change(): void
    {
        // Создаем модель
        $model = GenerationModel::create([
            'name' => 'test-model',
            'price_in' => 100.0,
            'price_out' => 200.0
        ]);

        // Создаем пользователя и проект
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        $chat = LLMChat::factory()->create();

        // Создаем задачу
        $task = AgentTask::create([
            'project_id' => $project->id,
            'type' => 'text',
            'status' => 'success',
            'agent_model_name' => 'test-model',
            'prompt_tokens' => 1000,
            'completion_tokens' => 500,
            'cost' => 150, // Изначальная стоимость
            'created_by' => $user->id,
            'chat_id' => $chat->id
        ]);

        $originalCost = $task->cost;

        // Меняем нерелевантные поля
        $task->status = 'processing';
        $task->type = 'code';
        
        // Вызываем обсервер
        $this->observer->updating($task);

        // Проверяем, что стоимость не изменилась
        $this->assertEquals($originalCost, $task->cost);
    }

    public function test_observer_does_not_recalculate_when_model_is_empty(): void
    {
        // Создаем пользователя и проект
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        $chat = LLMChat::factory()->create();

        // Создаем задачу без модели
        $task = AgentTask::create([
            'project_id' => $project->id,
            'type' => 'text',
            'status' => 'success',
            'agent_model_name' => null,
            'prompt_tokens' => 1000,
            'completion_tokens' => 500,
            'cost' => 0,
            'created_by' => $user->id,
            'chat_id' => $chat->id
        ]);

        $originalCost = $task->cost;

        // Меняем токены
        $task->prompt_tokens = 2000;
        $task->completion_tokens = 1000;
        
        // Вызываем обсервер
        $this->observer->updating($task);

        // Проверяем, что стоимость не изменилась (модель пустая)
        $this->assertEquals($originalCost, $task->cost);
    }
}
