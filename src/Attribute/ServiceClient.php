<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class ServiceClient
{
    public function __construct(
        public string $name,
    ) {}
}
