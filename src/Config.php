<?php

declare(strict_types=1);

namespace Vikunja;

final class Config
{
    public function __construct(
        public readonly string $baseUrl,
        public readonly string $apiKey,
    ) {}
}
