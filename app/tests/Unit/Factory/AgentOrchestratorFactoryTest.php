<?php

namespace Tests\Unit\Factory;

use App\Factory\AgentOrchestratorFactory;
use App\Services\AgentOrchestrator\FakeAgentOrchestratorService;
use App\Services\AgentOrchestrator\RemoteAgentOrchestratorService;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AgentOrchestratorFactoryTest extends TestCase
{
    #[Test]
    public function it_creates_fake_service_when_server_url_is_default()
    {
        // Arrange
        Config::set('services.agent_orchestrator.server_url', 'http://localhost:8080');
        Config::set('services.agent_orchestrator.timeout', 30);

        // Act
        $service = AgentOrchestratorFactory::create();

        // Assert
        $this->assertInstanceOf(FakeAgentOrchestratorService::class, $service);
    }

    #[Test]
    public function it_creates_fake_service_when_server_url_is_empty()
    {
        // Arrange
        Config::set('services.agent_orchestrator.server_url', '');
        Config::set('services.agent_orchestrator.timeout', 30);

        // Act
        $service = AgentOrchestratorFactory::create();

        // Assert
        $this->assertInstanceOf(FakeAgentOrchestratorService::class, $service);
    }

    #[Test]
    public function it_creates_remote_service_when_server_url_is_configured()
    {
        // Arrange
        Config::set('services.agent_orchestrator.server_url', 'http://agent-svc');
        Config::set('services.agent_orchestrator.timeout', 30);

        // Act
        $service = AgentOrchestratorFactory::create();

        // Assert
        $this->assertInstanceOf(RemoteAgentOrchestratorService::class, $service);
    }
}

