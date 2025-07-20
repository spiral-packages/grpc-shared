<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class Event
{
    public function __construct(
        public string $description,
        public string $producer,
        public ?string $defaultTopic = null,
    ) {}
}
