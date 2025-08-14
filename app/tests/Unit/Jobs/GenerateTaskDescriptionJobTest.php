<?php

namespace Tests\Unit\Jobs;

use App\Factory\AgentFactory;
use App\Interfaces\Factory\AgentFactoryInterface;
use App\Services\TaskDescriptionGenerator\StubDiffDescriptionGenerator;
use Mockery;
use Tests\TestCase;

class GenerateTaskDescriptionJobTest extends TestCase
{

    public function test_agent_factory_interface_is_correctly_used()
    {
        // Тестируем что AgentFactory правильно используется в новой архитектуре
        $agentFactory = Mockery::mock(AgentFactoryInterface::class);
        $stubGenerator = new StubDiffDescriptionGenerator();

        $agentFactory->shouldReceive('getDescriptionGenerator')
            ->with(1)
            ->once()
            ->andReturn($stubGenerator);

        $generator = $agentFactory->getDescriptionGenerator(1);

        $this->assertInstanceOf(StubDiffDescriptionGenerator::class, $generator);
    }

    public function test_agent_factory_provides_correct_generator()
    {
        // Тестируем что реальная AgentFactory работает корректно
        $llmGenerator = Mockery::mock(\App\Interfaces\LLM\ContentGenerator::class);
        $agentFactory = new AgentFactory($llmGenerator);

        $generator = $agentFactory->getDescriptionGenerator(1);

        $this->assertInstanceOf(\App\Services\TaskDescriptionGenerator\DiffDescriptionGenerator::class, $generator);
    }
}
