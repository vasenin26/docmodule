<?php

namespace App\Common\RestApiClient;

use Psr\Http\Message\RequestInterface;

interface Command extends RequestInterface
{
    public function execute(RestApiClient $client): mixed;
}
