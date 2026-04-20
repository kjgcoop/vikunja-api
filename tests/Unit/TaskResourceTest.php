<?php

declare(strict_types=1);

namespace Vikunja\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Vikunja\Exception\VikunjaException;
use Vikunja\VikunjaClient;

final class TaskResourceTest extends TestCase
{
    private function makeClient(array $responses): VikunjaClient
    {
        $mock    = new MockHandler($responses);
        $handler = HandlerStack::create($mock);
        $http    = new Client(['handler' => $handler, 'base_uri' => 'http://example.com/api/v1/']);

        return new VikunjaClient('http://example.com/api/v1/', 'test-key', $http);
    }

    private function taskFixture(int $id = 1): array
    {
        return [
            'id'         => $id,
            'title'      => "Task {$id}",
            'done'       => false,
            'project_id' => 5,
            'labels'     => [],
            'assignees'  => [],
            'created'    => '2026-03-01T10:00:00Z',
            'updated'    => '2026-04-01T10:00:00Z',
        ];
    }

    public function testSinglePageReturnsAllTasks(): void
    {
        $body   = json_encode([$this->taskFixture(1), $this->taskFixture(2)]);
        $client = $this->makeClient([
            new Response(200, ['X-Pagination-Total-Pages' => '1'], $body),
        ]);

        $tasks = $client->tasks()->forView(5, 3);

        $this->assertCount(2, $tasks);
        $this->assertSame(1, $tasks[0]['id']);
        $this->assertSame('Task 1', $tasks[0]['title']);
        $this->assertSame(2, $tasks[1]['id']);
    }

    public function testAutoPaginatesAcrossMultiplePages(): void
    {
        $client = $this->makeClient([
            new Response(200, ['X-Pagination-Total-Pages' => '3'], json_encode([$this->taskFixture(1)])),
            new Response(200, ['X-Pagination-Total-Pages' => '3'], json_encode([$this->taskFixture(2)])),
            new Response(200, ['X-Pagination-Total-Pages' => '3'], json_encode([$this->taskFixture(3)])),
        ]);

        $tasks = $client->tasks()->forView(5, 3);

        $this->assertCount(3, $tasks);
        $this->assertSame(1, $tasks[0]['id']);
        $this->assertSame(2, $tasks[1]['id']);
        $this->assertSame(3, $tasks[2]['id']);
    }

    public function testEmptyResponseReturnsEmptyArray(): void
    {
        $client = $this->makeClient([
            new Response(200, ['X-Pagination-Total-Pages' => '1'], '[]'),
        ]);

        $this->assertSame([], $client->tasks()->forView(5, 3));
    }

    public function testHttpErrorThrowsVikunjaException(): void
    {
        $client = $this->makeClient([
            new Response(401, [], '{"message":"Unauthorized"}'),
        ]);

        $this->expectException(VikunjaException::class);
        $client->tasks()->forView(5, 3);
    }
}
