<?php

declare(strict_types=1);

namespace Kjgcoop\Vikunja;

use GuzzleHttp\Client;
use Kjgcoop\Vikunja\Resource\AttachmentResource;
use Kjgcoop\Vikunja\Resource\ProjectResource;
use Kjgcoop\Vikunja\Resource\TaskResource;

final class VikunjaClient
{
    private readonly Client $http;

    public function __construct(string $baseUrl, string $apiKey, ?Client $http = null)
    {
        $this->http = $http ?? new Client([
            'base_uri'        => rtrim($baseUrl, '/') . '/',
            'timeout'         => 30,
            'connect_timeout' => 10,
            'headers'         => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept'        => 'application/json',
            ],
        ]);
    }

    public function tasks(): TaskResource
    {
        return new TaskResource($this->http);
    }

    public function projects(): ProjectResource
    {
        return new ProjectResource($this->http);
    }

    public function attachments(): AttachmentResource
    {
        return new AttachmentResource($this->http);
    }
}
