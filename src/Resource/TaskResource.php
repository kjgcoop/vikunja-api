<?php

declare(strict_types=1);

namespace Vikunja\Resource;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Vikunja\DTO\Task;
use Vikunja\Exception\VikunjaException;

final class TaskResource
{
    public function __construct(private readonly Client $http) {}

    /**
     * @param  array<string,mixed> $params  Optional query params: page, per_page, s, sort_by,
     *                                      order_by, filter, filter_timezone, filter_include_nulls
     * @return Task[]
     * @throws VikunjaException
     */
    public function forView(int $projectId, int $viewId, array $params = []): array
    {
        $tasks   = [];
        $page    = 1;
        $perPage = (int) ($params['per_page'] ?? 50);

        unset($params['page']);

        do {
            $query = array_merge($params, ['page' => $page, 'per_page' => $perPage]);

            try {
                $response = $this->http->get(
                    "projects/{$projectId}/views/{$viewId}/tasks",
                    ['query' => $query],
                );
            } catch (GuzzleException $e) {
                throw VikunjaException::fromGuzzle($e);
            }

            $body       = (string) $response->getBody();
            $items      = json_decode($body, true) ?? [];
            $totalPages = (int) ($response->getHeaderLine('X-Pagination-Total-Pages') ?: 1);

            foreach ($items as $item) {
                $tasks[] = Task::fromArray($item);
            }

            $page++;
        } while ($page <= $totalPages);

        return $tasks;
    }
}
