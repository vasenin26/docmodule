<?php

namespace App\Services\AgentOrchestrator\HttpClient\Command;

use App\Common\DTO\RemoteAgent\AgentMeta;
use App\Common\DTO\RemoteAgent\ConfigOptions;
use App\Common\RestApiClient\Command;
use App\Common\RestApiClient\RestApiClient;
use GuzzleHttp\Psr7\Request;
use RuntimeException;

class StartAgentCommand extends Request implements Command
{
    public function __construct(
        private string $baseUrl,
        private ConfigOptions $configOptions
    ) {
        parent::__construct(
            'POST',
            $baseUrl . '/orchestrator/start-agent',
            [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            json_encode([
                'agentId' => $configOptions->agentId->toString(),
                'token' => $configOptions->token
            ])
        );
    }

    public function execute(RestApiClient $client): mixed
    {
        $response = $client->execute($this);

        if ($response->getStatusCode() !== 201) {
            throw new RuntimeException(
                'Failed to start agent. Status: ' . $response->getStatusCode()
            );
        }

        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        if (!isset($data['server'], $data['agentId'], $data['publicKey'])) {
            throw new RuntimeException('Invalid response format from orchestrator');
        }

        return new AgentMeta(
            server: $data['server'],
            agentId: $data['agentId'],
            publicKey: $data['publicKey']
        );
    }
}

