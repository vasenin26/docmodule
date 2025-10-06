<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Project;
use App\Services\AgentJwtService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrchestratorAgentSeeder extends Seeder
{
    public function __construct(
        private AgentJwtService $jwtService
    ) {}

    public function run(): void
    {
        // Получаем или создаем системный проект
        $project = Project::firstOrCreate(
            ['title' => 'System'],
            ['owner_id' => 1]
        );

        // Проверяем, существует ли уже агент-оркестратор
        $existingAgent = Agent::where('name', 'Orchestrator Agent')
            ->where('has_cross_project_access', true)
            ->first();

        if ($existingAgent) {
            $this->command->info('Orchestrator Agent already exists (ID: ' . $existingAgent->id . ')');
            return;
        }

        // Создаем агента
        $agent = Agent::create([
            'name' => 'Orchestrator Agent',
            'uuid' => Str::uuid()->toString(),
            'token' => '', // Временно
            'project_id' => $project->id,
            'has_cross_project_access' => true,
        ]);

        // Генерируем JWT токен
        $token = $this->jwtService->generateToken($agent);
        $agent->update(['token' => $token]);

        $this->command->info('Orchestrator Agent created successfully!');
        $this->command->info('Agent ID: ' . $agent->id);
        $this->command->info('Agent UUID: ' . $agent->uuid);
        $this->command->line('JWT Token: ' . $token);
    }
}
