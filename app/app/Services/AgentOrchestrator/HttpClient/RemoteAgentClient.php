<?php

namespace App\Services\AgentOrchestrator\HttpClient;

use App\Common\DTO\RemoteAgent\AgentMeta;
use App\Common\DTO\RemoteAgent\ConfigOptions;
use App\Common\RestApiClient\RestApiClient;
use App\Services\AgentOrchestrator\HttpClient\Command\StartAgentCommand;
use App\Services\AgentOrchestrator\HttpClient\Command\StartProcessCommand;
use App\Services\AgentOrchestrator\HttpClient\Command\StopAgentCommand;
use Illuminate\Support\Facades\Log;

class RemoteAgentClient
{
    private RestApiClient $client;

    public function __construct(
        private string $baseUrl,
        private int $timeout = 30
    ) {
        $httpClient = new \GuzzleHttp\Client([
            'timeout' => $this->timeout,
            'connect_timeout' => 10,
            'http_errors' => false, // Обрабатываем ошибки вручную
        ]);

        $this->client = new RestApiClient($httpClient);
    }

    public function startAgent(ConfigOptions $configOptions): AgentMeta
    {
        Log::info('RemoteAgentClient: Sending startAgent request', [
            'agent_id' => $configOptions->agentId->toString(),
            'base_url' => $this->baseUrl,
        ]);

        $command = new StartAgentCommand($this->baseUrl, $configOptions);
        $result = $command->execute($this->client);

        Log::info('RemoteAgentClient: Agent started successfully', [
            'agent_id' => $result->agentId,
            'server' => $result->server,
        ]);

        return $result;
    }

    public function stopAgent(string $agentId): array
    {
        Log::info('RemoteAgentClient: Sending stopAgent request', [
            'agent_id' => $agentId,
            'base_url' => $this->baseUrl,
        ]);

        $command = new StopAgentCommand($this->baseUrl, $agentId);
        $result = $command->execute($this->client);

        Log::info('RemoteAgentClient: Agent stopped successfully', [
            'agent_id' => $agentId,
            'response' => $result,
        ]);

        return $result;
    }

    public function startProcess(string $taskType): void
    {
        Log::info('RemoteAgentClient: Sending startProcess request', [
            'task_type' => $taskType,
            'base_url' => $this->baseUrl,
        ]);

        $command = new StartProcessCommand($this->baseUrl, $taskType);
        $command->execute($this->client);

        Log::info('RemoteAgentClient: Process started successfully', [
            'task_type' => $taskType,
        ]);
    }
}

