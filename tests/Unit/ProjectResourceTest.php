<?php

declare(strict_types=1);

namespace Kjgcoop\Vikunja\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Kjgcoop\Vikunja\Exception\VikunjaException;
use Kjgcoop\Vikunja\VikunjaClient;
use PHPUnit\Framework\TestCase;

final class ProjectResourceTest extends TestCase
{
    private function makeClient(array $responses): VikunjaClient
    {
        $http = new Client([
            'handler'  => HandlerStack::create(new MockHandler($responses)),
            'base_uri' => 'http://example.com/api/v1/',
        ]);

        return new VikunjaClient('http://example.com/api/v1/', 'test-key', $http);
    }

    public function testGetReturnsProject(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['id' => 5, 'title' => 'Garden', 'is_archived' => true])),
        ]);

        $project = $client->projects()->get(5);

        $this->assertSame('Garden', $project->title);
        $this->assertTrue($project->is_archived);
    }

    public function testViewsReturnsViews(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode([
                ['id' => 1, 'title' => 'List', 'view_kind' => 'list'],
                ['id' => 4, 'title' => 'Kanban', 'view_kind' => 'kanban'],
            ])),
        ]);

        $views = $client->projects()->views(5);

        $this->assertCount(2, $views);
        $this->assertSame('kanban', $views[1]->view_kind);
    }

    public function testNotFoundThrowsVikunjaException(): void
    {
        $client = $this->makeClient([new Response(404, [], '{"message":"not found"}')]);

        $this->expectException(VikunjaException::class);
        $client->projects()->get(999);
    }

    public function testDownloadWritesFile(): void
    {
        $client = $this->makeClient([new Response(200, [], 'file contents')]);
        $dest   = tempnam(sys_get_temp_dir(), 'vik');

        $client->attachments()->download(7, 3, $dest);

        $this->assertSame('file contents', file_get_contents($dest));
        unlink($dest);
    }
}
