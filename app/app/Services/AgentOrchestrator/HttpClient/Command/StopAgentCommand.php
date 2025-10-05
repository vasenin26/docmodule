<?php

namespace App\Services\AgentOrchestrator\HttpClient\Command;

use App\Common\DTO\RemoteAgent\AgentMeta;
use App\Common\RestApiClient\Command;
use App\Common\RestApiClient\RestApiClient;
use GuzzleHttp\Psr7\Request;
use RuntimeException;

class StopAgentCommand extends Request implements Command
{
    public function __construct(
        private string $baseUrl,
        private string $agentId
    ) {
        parent::__construct(
            'POST',
            $baseUrl . '/orchestrator/stop-agent/' . $agentId,
            [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        );
    }

    public function execute(RestApiClient $client): mixed
    {
        $response = $client->execute($this);
        
        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException(
                'Failed to stop agent. Status: ' . $response->getStatusCode()
            );
        }

        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        return $data;
    }
}

