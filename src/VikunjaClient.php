<?php

declare(strict_types=1);

namespace Vikunja;

use GuzzleHttp\Client;
use Vikunja\Resource\TaskResource;

final class VikunjaClient
{
    private readonly Client $http;

    public function __construct(Config $config, ?Client $http = null)
    {
        $this->http = $http ?? new Client([
            'base_uri' => rtrim($config->baseUrl, '/') . '/',
            'headers'  => [
                'Authorization' => 'Bearer ' . $config->apiKey,
                'Accept'        => 'application/json',
            ],
        ]);
    }

    public function tasks(): TaskResource
    {
        return new TaskResource($this->http);
    }
}
