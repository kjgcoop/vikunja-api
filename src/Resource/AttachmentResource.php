<?php

declare(strict_types=1);

namespace Kjgcoop\Vikunja\Resource;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Kjgcoop\Vikunja\Exception\VikunjaException;

final class AttachmentResource
{
    public function __construct(private readonly Client $http) {}

    /**
     * Streams an attachment to disk.
     *
     * @throws VikunjaException
     */
    public function download(int $taskId, int $attachmentId, string $destination): void
    {
        try {
            $this->http->get(
                "tasks/{$taskId}/attachments/{$attachmentId}",
                [
                    'sink'    => $destination,
                    'headers' => ['Accept' => '*/*'],
                    'timeout' => 300,
                ],
            );
        } catch (GuzzleException $e) {
            throw VikunjaException::fromGuzzle($e);
        }
    }
}
