<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC\Security;

final readonly class AuthenticatedUser
{
    public function __construct(
        // todo: add user properties
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            // todo: initialize user properties from $data
        );
    }
}
