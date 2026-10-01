<?php

declare(strict_types=1);

namespace Kjgcoop\Vikunja\Resource;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Kjgcoop\Vikunja\Exception\VikunjaException;

final class ProjectResource
{
    public function __construct(private readonly Client $http) {}

    /**
     * Works for archived projects too.
     *
     * @throws VikunjaException
     */
    public function get(int $projectId): \stdClass
    {
        try {
            $response = $this->http->get("projects/{$projectId}");
        } catch (GuzzleException $e) {
            throw VikunjaException::fromGuzzle($e);
        }

        return json_decode((string) $response->getBody());
    }

    /**
     * @return array<int,\stdClass>  Each view has id, title and view_kind (list|gantt|table|kanban)
     * @throws VikunjaException
     */
    public function views(int $projectId): array
    {
        try {
            $response = $this->http->get("projects/{$projectId}/views");
        } catch (GuzzleException $e) {
            throw VikunjaException::fromGuzzle($e);
        }

        return json_decode((string) $response->getBody()) ?? [];
    }
}
