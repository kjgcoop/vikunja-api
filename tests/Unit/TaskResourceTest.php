<?php

declare(strict_types=1);

namespace Vikunja\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Vikunja\Config;
use Vikunja\DTO\Task;
use Vikunja\Exception\VikunjaException;
use Vikunja\Resource\TaskResource;
use Vikunja\VikunjaClient;

final class TaskResourceTest extends TestCase
{
    private function makeClient(array $responses): VikunjaClient
    {
        $mock    = new MockHandler($responses);
        $handler = HandlerStack::create($mock);
        $http    = new Client(['handler' => $handler, 'base_uri' => 'http://example.com/api/v1/']);
        $config  = new Config('http://example.com/api/v1/', 'test-key');

        return new VikunjaClient($config, $http);
    }

    private function taskFixture(int $id = 1, bool $done = false): array
    {
        return [
            'id'           => $id,
            'title'        => "Task {$id}",
            'description'  => 'A description',
            'done'         => $done,
            'done_at'      => $done ? '2026-01-01T12:00:00Z' : '0001-01-01T00:00:00Z',
            'due_date'     => '0001-01-01T00:00:00Z',
            'start_date'   => '0001-01-01T00:00:00Z',
            'end_date'     => '0001-01-01T00:00:00Z',
            'project_id'   => 5,
            'priority'     => 2,
            'percent_done' => 0.0,
            'identifier'   => "PROJ-{$id}",
            'hex_color'    => '',
            'is_favorite'  => false,
            'labels'       => [
                ['id' => 10, 'title' => 'bug', 'hex_color' => 'ff0000'],
            ],
            'assignees'    => [
                ['id' => 3, 'username' => 'alice', 'name' => 'Alice'],
            ],
            'created_by'   => ['id' => 1, 'username' => 'admin', 'name' => ''],
            'created'      => '2026-03-01T10:00:00Z',
            'updated'      => '2026-04-01T10:00:00Z',
        ];
    }

    public function testSinglePageReturnsAllTasks(): void
    {
        $body   = json_encode([$this->taskFixture(1), $this->taskFixture(2, true)]);
        $client = $this->makeClient([
            new Response(200, ['X-Pagination-Total-Pages' => '1'], $body),
        ]);

        $tasks = $client->tasks()->forView(5, 3);

        $this->assertCount(2, $tasks);
        $this->assertInstanceOf(Task::class, $tasks[0]);
        $this->assertSame(1, $tasks[0]->id);
        $this->assertSame('Task 1', $tasks[0]->title);
        $this->assertFalse($tasks[0]->done);
        $this->assertTrue($tasks[1]->done);
        $this->assertSame('2026-01-01T12:00:00Z', $tasks[1]->doneAt);
    }

    public function testDtosArePopulatedCorrectly(): void
    {
        $body   = json_encode([$this->taskFixture(7)]);
        $client = $this->makeClient([
            new Response(200, ['X-Pagination-Total-Pages' => '1'], $body),
        ]);

        $task = $client->tasks()->forView(5, 3)[0];

        $this->assertSame('PROJ-7', $task->identifier);
        $this->assertSame(5, $task->projectId);
        $this->assertNull($task->hexColor);
        $this->assertNull($task->dueDate);

        $this->assertCount(1, $task->labels);
        $this->assertSame('bug', $task->labels[0]->title);
        $this->assertSame('ff0000', $task->labels[0]->hexColor);

        $this->assertCount(1, $task->assignees);
        $this->assertSame('alice', $task->assignees[0]->username);
        $this->assertSame('Alice', $task->assignees[0]->name);

        $this->assertNotNull($task->createdBy);
        $this->assertSame('admin', $task->createdBy->username);
        $this->assertNull($task->createdBy->name);
    }

    public function testAutoPaginatesAcrossMultiplePages(): void
    {
        $page1 = json_encode([$this->taskFixture(1)]);
        $page2 = json_encode([$this->taskFixture(2)]);
        $page3 = json_encode([$this->taskFixture(3)]);

        $client = $this->makeClient([
            new Response(200, ['X-Pagination-Total-Pages' => '3'], $page1),
            new Response(200, ['X-Pagination-Total-Pages' => '3'], $page2),
            new Response(200, ['X-Pagination-Total-Pages' => '3'], $page3),
        ]);

        $tasks = $client->tasks()->forView(5, 3);

        $this->assertCount(3, $tasks);
        $this->assertSame(1, $tasks[0]->id);
        $this->assertSame(2, $tasks[1]->id);
        $this->assertSame(3, $tasks[2]->id);
    }

    public function testEmptyResponseReturnsEmptyArray(): void
    {
        $client = $this->makeClient([
            new Response(200, ['X-Pagination-Total-Pages' => '1'], '[]'),
        ]);

        $tasks = $client->tasks()->forView(5, 3);

        $this->assertSame([], $tasks);
    }

    public function testHttpErrorThrowsVikunjaException(): void
    {
        $client = $this->makeClient([
            new Response(401, [], '{"message":"Unauthorized"}'),
        ]);

        $this->expectException(VikunjaException::class);
        $client->tasks()->forView(5, 3);
    }

    public function testConfigStoresValues(): void
    {
        $config = new Config('http://example.com/api/v1/', 'my-key');

        $this->assertSame('http://example.com/api/v1/', $config->baseUrl);
        $this->assertSame('my-key', $config->apiKey);
    }
}
