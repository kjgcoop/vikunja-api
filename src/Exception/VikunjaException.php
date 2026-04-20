<?php

declare(strict_types=1);

namespace Vikunja\Exception;

use GuzzleHttp\Exception\GuzzleException;

final class VikunjaException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public static function fromGuzzle(GuzzleException $e): self
    {
        $status = 0;
        if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
            $status = $e->getResponse()->getStatusCode();
        }

        return new self($e->getMessage(), $status, $e);
    }
}
