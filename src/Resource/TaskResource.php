<?php

declare(strict_types=1);

namespace Kjgcoop\Vikunja\Resource;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Kjgcoop\Vikunja\Exception\VikunjaException;

final class TaskResource
{
    public function __construct(private readonly Client $http) {}

    /**
     * @param  array<string,mixed> $params  Optional: page, per_page, s, sort_by, order_by,
     *                                      filter, filter_timezone, filter_include_nulls
     * @return array<int,array<string,mixed>>
     * @throws VikunjaException
     */
    public function forView(int $projectId, int $viewId, array $params = []): array
    {
        $tasks   = [];
        $buckets = [];
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

            $items      = json_decode((string) $response->getBody()) ?? [];
            $totalPages = (int) ($response->getHeaderLine('X-Pagination-Total-Pages') ?: 1);

            foreach ($items as $bucket) {
                $buckets[] = $bucket;
            }

            $page++;
        } while ($page <= $totalPages);

        return $buckets;
    }
}
