<?php

declare(strict_types=1);

namespace Kjgcoop\Vikunja\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Kjgcoop\Vikunja\Exception\VikunjaException;
use Kjgcoop\Vikunja\VikunjaClient;

final class TaskResourceTest extends TestCase
{
    private function makeClient(array $responses): VikunjaClient
    {
        $mock    = new MockHandler($responses);
        $handler = HandlerStack::create($mock);
        $http    = new Client(['handler' => $handler, 'base_uri' => 'http://example.com/api/v1/']);

        return new VikunjaClient('http://example.com/api/v1/', 'test-key', $http);
    }

    private function bucketFixture(int $bucketId, array $tasks = []): array
    {
        return [
            'id'              => $bucketId,
            'title'           => "Bucket {$bucketId}",
            'project_view_id' => 468,
            'limit'           => 0,
            'count'           => count($tasks),
            'position'        => $bucketId * 100,
            'created'         => '2026-03-01T10:00:00Z',
            'updated'         => '2026-03-01T10:00:00Z',
            'tasks'           => $tasks,
        ];
    }

    private function taskFixture(int $id, int $bucketId = 1): array
    {
        return [
            'id'        => $id,
            'title'     => "Task {$id}",
            'done'      => false,
            'project_id'=> 5,
            'bucket_id' => $bucketId,
            'created'   => '2026-03-01T10:00:00Z',
            'updated'   => '2026-04-01T10:00:00Z',
        ];
    }

    public function testReturnsBucketsWithTheirTasks(): void
    {
        $body = json_encode([
            $this->bucketFixture(1, []),                                                       // empty bucket
            $this->bucketFixture(2, [$this->taskFixture(101, 2)]),                             // one task
            $this->bucketFixture(3, [$this->taskFixture(102, 3), $this->taskFixture(103, 3)]), // two tasks
        ]);

        $client = $this->makeClient([
            new Response(200, ['X-Pagination-Total-Pages' => '1'], $body),
        ]);

        $buckets = $client->tasks()->forView(5, 468);

        $this->assertCount(3, $buckets);
        $this->assertSame([], $buckets[0]->tasks);
        $this->assertSame(101, $buckets[1]->tasks[0]->id);
        $this->assertSame(102, $buckets[2]->tasks[0]->id);
        $this->assertSame(103, $buckets[2]->tasks[1]->id);
    }

    public function testEmptyBucketsStayEmpty(): void
    {
        $body = json_encode([
            $this->bucketFixture(1, []),
            $this->bucketFixture(2, []),
        ]);

        $client = $this->makeClient([
            new Response(200, ['X-Pagination-Total-Pages' => '1'], $body),
        ]);

        $buckets = $client->tasks()->forView(5, 468);

        $this->assertCount(2, $buckets);
        $this->assertSame([], $buckets[0]->tasks);
    }

    public function testPaginationMergesTasksIntoTheSameBucket(): void
    {
        $client = $this->makeClient([
            new Response(200, ['X-Pagination-Total-Pages' => '2'], json_encode([
                $this->bucketFixture(1, [$this->taskFixture(1, 1)]),
                $this->bucketFixture(2, [$this->taskFixture(2, 2)]),
            ])),
            new Response(200, ['X-Pagination-Total-Pages' => '2'], json_encode([
                $this->bucketFixture(1, [$this->taskFixture(3, 1)]),
                $this->bucketFixture(2, []),
            ])),
        ]);

        $buckets = $client->tasks()->forView(5, 468);

        $this->assertCount(2, $buckets);
        $this->assertSame([1, 3], array_map(fn ($t) => $t->id, $buckets[0]->tasks));
        $this->assertSame([2], array_map(fn ($t) => $t->id, $buckets[1]->tasks));
    }

    public function testHttpErrorThrowsVikunjaException(): void
    {
        $client = $this->makeClient([
            new Response(401, [], '{"message":"Unauthorized"}'),
        ]);

        $this->expectException(VikunjaException::class);
        $client->tasks()->forView(5, 468);
    }
}
