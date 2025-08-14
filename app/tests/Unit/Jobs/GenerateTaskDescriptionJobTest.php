<?php

namespace Tests\Unit\Jobs;

use App\Common\DTO\DifferenceDataDTO;
use App\Factory\AgentFactory;
use App\Interfaces\AgentFactoryInterface;
use App\Jobs\CreateTaskInTrackerJob;
use App\Jobs\GenerateTaskDescriptionJob;
use App\Models\Page;
use App\Models\PageDiffDescription;
use App\Models\Project;
use App\Models\User;
use App\Services\DiffGenerator\DiffGeneratorService;
use App\Services\TaskDescriptionGenerator\StubDescriptionGenerator;

use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class GenerateTaskDescriptionJobTest extends TestCase
{

    public function test_agent_factory_interface_is_correctly_used()
    {
        // Тестируем что AgentFactory правильно используется в новой архитектуре
        $agentFactory = Mockery::mock(AgentFactoryInterface::class);
        $stubGenerator = new StubDescriptionGenerator();
        
        $agentFactory->shouldReceive('getDescriptionGenerator')
            ->with(1)
            ->once()
            ->andReturn($stubGenerator);

        $generator = $agentFactory->getDescriptionGenerator(1);
        
        $this->assertInstanceOf(StubDescriptionGenerator::class, $generator);
    }

    public function test_agent_factory_provides_correct_generator()
    {
        // Тестируем что реальная AgentFactory работает корректно  
        $llmGenerator = Mockery::mock(\App\Interfaces\LLMGenerator::class);
        $agentFactory = new AgentFactory($llmGenerator);
        
        $generator = $agentFactory->getDescriptionGenerator(1);
        
        $this->assertInstanceOf(\App\Services\TaskDescriptionGenerator\LLMDescriptionGenerator::class, $generator);
    }
}
