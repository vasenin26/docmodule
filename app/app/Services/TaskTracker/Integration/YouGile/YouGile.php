<?php

namespace App\Services\TaskTracker\Integration;

use App\Interfaces\KeyProviderInterface;
use App\Interfaces\TaskTrackerInterface;
use http\Client;

class YouGile implements TaskTrackerInterface
{
    public function __construct(
        private KeyProviderInterface $keyProvider,
        private Client               $httpClient,
    )
    {
    }

    public function factory(KeyProviderInterface $keyProvider): TaskTrackerInterface
    {
        return new self($keyProvider);
    }

    public function createTask(string $title, string $description): bool
    {
        $key = $this->keyProvider->getKey();

        throw_if(is_null($key));

        $token = (new AuthCommand($key))->execute($this->httpClient);
        $taks = (new CreateTaskCommand($token, $title, $description))->execute($this->httpClient);

        return true;
    }

    private function auth(string $login, string $password, string $companyId): string
    {
        $response = $this->httpClient->post(
            'https://ru.yougile.com/api-v2/auth/keys',
            [
                'Content-Type' => 'application/json'
            ],
            json_encode([
                'login' => $login,
                'password' => $password,
                'companyId' => $companyId
            ])
        );

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Failed to authenticate with YouGile');
        }

        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        if (!isset($data['key'])) {
            throw new \RuntimeException('Token not found in YouGile response');
        }

        return $data['key'];
    }

}
