<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC\Service;

final readonly class Service
{
    public function __construct(
        public string $service,
        public string $version,
    ) {}
}
