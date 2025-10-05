<?php

namespace App\Services\AgentOrchestrator\HttpClient\Command;

use App\Common\RestApiClient\Command;
use App\Common\RestApiClient\RestApiClient;
use GuzzleHttp\Psr7\Request;
use RuntimeException;

class StartProcessCommand extends Request implements Command
{
    public function __construct(
        private string $baseUrl,
        private string $taskType
    ) {
        parent::__construct(
            'POST',
            $baseUrl . '/orchestrator/start-process',
            [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            json_encode([
                'taskType' => $taskType
            ])
        );
    }

    public function execute(RestApiClient $client): mixed
    {
        $response = $client->execute($this);
        
        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException(
                'Failed to start process. Status: ' . $response->getStatusCode()
            );
        }
        
        return null;
    }
}

