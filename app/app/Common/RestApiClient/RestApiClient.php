<?php

namespace App\Common\RestApiClient;

use Psr\Http\Message\ResponseInterface;

class RestApiClient
{
    public function __construct(
        private \GuzzleHttp\Client $httpClient,
    )
    {
    }

    public function execute(Command $command): ResponseInterface
    {
        return $this->httpClient->sendRequest($command);
    }
}
