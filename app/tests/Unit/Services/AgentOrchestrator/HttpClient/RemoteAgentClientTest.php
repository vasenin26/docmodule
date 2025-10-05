<?php

namespace Tests\Unit\Services\AgentOrchestrator\HttpClient;

use App\Common\DTO\RemoteAgent\AgentMeta;
use App\Common\DTO\RemoteAgent\ConfigOptions;
use App\Services\AgentOrchestrator\HttpClient\RemoteAgentClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Tests\TestCase;

class RemoteAgentClientTest extends TestCase
{
    #[Test]
    public function it_starts_agent_successfully()
    {
        // Arrange
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'server' => 'test-server',
                'agentId' => '550e8400-e29b-41d4-a716-446655440000',
                'publicKey' => 'ssh-rsa AAAA...'
            ]))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);
        
        // Создаем клиент с mock HTTP client
        $client = new RemoteAgentClient('http://test-server', 30);
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $restApiClient = $property->getValue($client);
        
        $clientProperty = new \ReflectionClass($restApiClient);
        $httpClientProperty = $clientProperty->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($restApiClient, $httpClient);

        $configOptions = new ConfigOptions(
            agentId: Uuid::fromString('550e8400-e29b-41d4-a716-446655440000'),
            token: 'test-token'
        );

        // Act
        $result = $client->startAgent($configOptions);

        // Assert
        $this->assertInstanceOf(AgentMeta::class, $result);
        $this->assertEquals('test-server', $result->server);
        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $result->agentId);
        $this->assertEquals('ssh-rsa AAAA...', $result->publicKey);
    }

    #[Test]
    public function it_throws_exception_on_failed_start_agent()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to start agent');

        // Arrange
        $mock = new MockHandler([
            new Response(500, [], 'Internal Server Error')
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);
        
        $client = new RemoteAgentClient('http://test-server', 30);
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $restApiClient = $property->getValue($client);
        
        $clientProperty = new \ReflectionClass($restApiClient);
        $httpClientProperty = $clientProperty->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($restApiClient, $httpClient);

        $configOptions = new ConfigOptions(
            agentId: Uuid::fromString('550e8400-e29b-41d4-a716-446655440000'),
            token: 'test-token'
        );

        // Act
        $client->startAgent($configOptions);
    }

    #[Test]
    public function it_stops_agent_successfully()
    {
        // Arrange
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'status' => 'agent stopped'
            ]))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);
        
        $client = new RemoteAgentClient('http://test-server', 30);
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $restApiClient = $property->getValue($client);
        
        $clientProperty = new \ReflectionClass($restApiClient);
        $httpClientProperty = $clientProperty->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($restApiClient, $httpClient);

        // Act
        $result = $client->stopAgent('550e8400-e29b-41d4-a716-446655440000');

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('agent stopped', $result['status']);
    }

    #[Test]
    public function it_starts_process_successfully()
    {
        // Arrange
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'status' => 'process started'
            ]))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);
        
        $client = new RemoteAgentClient('http://test-server', 30);
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $restApiClient = $property->getValue($client);
        
        $clientProperty = new \ReflectionClass($restApiClient);
        $httpClientProperty = $clientProperty->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($restApiClient, $httpClient);

        // Act & Assert (no exception thrown)
        $client->startProcess('test-task-type');
        
        $this->assertTrue(true); // If we reach here, no exception was thrown
    }
}

