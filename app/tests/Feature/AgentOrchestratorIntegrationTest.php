<?php

namespace Tests\Feature;

use App\Common\DTO\RemoteAgent\ConfigOptions;
use App\Interfaces\AgentOrchestratorInterface;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class AgentOrchestratorIntegrationTest extends TestCase
{
    #[Test]
    public function it_resolves_fake_orchestrator_from_container()
    {
        // Arrange
        Config::set('services.agent_orchestrator.server_url', 'http://localhost:8080');

        // Act
        $orchestrator = app(AgentOrchestratorInterface::class);

        // Assert
        $this->assertInstanceOf(AgentOrchestratorInterface::class, $orchestrator);
        $this->assertInstanceOf(\App\Services\AgentOrchestrator\FakeAgentOrchestratorService::class, $orchestrator);
    }

    #[Test]
    public function it_resolves_remote_orchestrator_from_container()
    {
        // Arrange
        Config::set('services.agent_orchestrator.server_url', 'http://agent-svc');

        // Act
        $orchestrator = app(AgentOrchestratorInterface::class);

        // Assert
        $this->assertInstanceOf(AgentOrchestratorInterface::class, $orchestrator);
        $this->assertInstanceOf(\App\Services\AgentOrchestrator\RemoteAgentOrchestratorService::class, $orchestrator);
    }

    #[Test]
    public function it_uses_singleton_binding()
    {
        // Arrange
        Config::set('services.agent_orchestrator.server_url', 'http://localhost:8080');

        // Act
        $orchestrator1 = app(AgentOrchestratorInterface::class);
        $orchestrator2 = app(AgentOrchestratorInterface::class);

        // Assert
        $this->assertSame($orchestrator1, $orchestrator2);
    }

    #[Test]
    public function it_works_with_existing_job_integration()
    {
        // Arrange
        Config::set('services.agent_orchestrator.server_url', 'http://localhost:8080');
        $orchestrator = app(AgentOrchestratorInterface::class);

        // Act & Assert (no exception)
        $orchestrator->startProcess('test-task-type');
        
        $this->assertTrue(true);
    }
}

