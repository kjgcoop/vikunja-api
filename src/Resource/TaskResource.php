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
     * @return array<int,\stdClass>  For kanban views, one object per bucket, each with a `tasks` array
     * @throws VikunjaException
     */
    public function forView(int $projectId, int $viewId, array $params = []): array
    {
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

            // On kanban views, pagination applies to the tasks inside each bucket, so the same
            // buckets come back on every page. Merge by bucket ID instead of appending.
            foreach ($items as $bucket) {
                // Non-kanban views return flat tasks rather than buckets; leave those as they were.
                if (!property_exists($bucket, 'tasks')) {
                    $buckets[] = $bucket;
                    continue;
                }

                $bucket->tasks ??= [];

                if (!isset($buckets[$bucket->id])) {
                    $buckets[$bucket->id] = $bucket;
                    continue;
                }

                if (isset($buckets[$bucket->id]->tasks)) {
                    array_push($buckets[$bucket->id]->tasks, ...$bucket->tasks);
                }
            }

            $page++;
        } while ($page <= $totalPages);

        return array_values($buckets);
    }
}
