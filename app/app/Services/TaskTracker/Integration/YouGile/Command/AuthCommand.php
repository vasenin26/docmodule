<?php

namespace App\Services\TaskTracker\Integration\YouGile\Command;

use App\Common\RestApiClient\Command;
use App\Common\RestApiClient\RestApiClient;
use GuzzleHttp\Psr7\Request;

class AuthCommand extends Request implements Command
{
    public function __construct(
        private string $login,
        private string $password,
        private string $companyId
    ) {
        parent::__construct(
            'POST',
            'https://ru.yougile.com/api-v2/auth/keys',
            [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            json_encode([
                'login' => $this->login,
                'password' => $this->password,
                'companyId' => $this->companyId
            ])
        );
    }

    public function execute(RestApiClient $client): string
    {
        $response = $client->execute($this);
        
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException(
                'Failed to authenticate with YouGile. Status: ' . $response->getStatusCode()
            );
        }

        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        if (!isset($data['key'])) {
            throw new \RuntimeException('Token not found in YouGile response');
        }

        return $data['key'];
    }
}
