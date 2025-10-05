<?php

namespace Tests\Unit\Services;

use App\Common\DTO\RemoteAgent\AgentMeta;
use App\Common\DTO\RemoteAgent\ConfigOptions;
use App\Services\AgentOrchestrator\HttpClient\RemoteAgentClient;
use App\Services\AgentOrchestrator\RemoteAgentOrchestratorService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Tests\TestCase;

class RemoteAgentOrchestratorServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_starts_agent_successfully()
    {
        // Arrange
        $configOptions = new ConfigOptions(
            agentId: Uuid::fromString('550e8400-e29b-41d4-a716-446655440000'),
            token: 'test-token'
        );

        $expectedMeta = new AgentMeta(
            server: 'test-server',
            agentId: '550e8400-e29b-41d4-a716-446655440000',
            publicKey: 'ssh-rsa AAAA...'
        );

        $mockClient = Mockery::mock(RemoteAgentClient::class);
        $mockClient->shouldReceive('startAgent')
            ->once()
            ->with($configOptions)
            ->andReturn($expectedMeta);

        $service = new RemoteAgentOrchestratorService('http://test-server');
        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($service, $mockClient);

        // Act
        $result = $service->startAgent($configOptions);

        // Assert
        $this->assertSame($expectedMeta, $result);
    }

    #[Test]
    public function it_propagates_exception_on_start_agent_failure()
    {
        $this->expectException(RuntimeException::class);

        // Arrange
        $configOptions = new ConfigOptions(
            agentId: Uuid::fromString('550e8400-e29b-41d4-a716-446655440000'),
            token: 'test-token'
        );

        $mockClient = Mockery::mock(RemoteAgentClient::class);
        $mockClient->shouldReceive('startAgent')
            ->once()
            ->with($configOptions)
            ->andThrow(new RuntimeException('Connection error'));

        $service = new RemoteAgentOrchestratorService('http://test-server');
        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($service, $mockClient);

        // Act
        $service->startAgent($configOptions);
    }

    #[Test]
    public function it_stops_agent_successfully()
    {
        // Arrange
        $agentMeta = new AgentMeta(
            server: 'test-server',
            agentId: '550e8400-e29b-41d4-a716-446655440000',
            publicKey: 'ssh-rsa AAAA...'
        );

        $mockClient = Mockery::mock(RemoteAgentClient::class);
        $mockClient->shouldReceive('stopAgent')
            ->once()
            ->with('550e8400-e29b-41d4-a716-446655440000')
            ->andReturn(['status' => 'agent stopped']);

        $service = new RemoteAgentOrchestratorService('http://test-server');
        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($service, $mockClient);

        // Act
        $result = $service->stopAgent($agentMeta);

        // Assert
        $this->assertSame($agentMeta, $result);
    }

    #[Test]
    public function it_starts_process_successfully()
    {
        // Arrange
        $mockClient = Mockery::mock(RemoteAgentClient::class);
        $mockClient->shouldReceive('startProcess')
            ->once()
            ->with('test-task-type')
            ->andReturnNull();

        $service = new RemoteAgentOrchestratorService('http://test-server');
        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($service, $mockClient);

        // Act & Assert (no exception)
        $service->startProcess('test-task-type');
        
        $this->assertTrue(true);
    }
}

