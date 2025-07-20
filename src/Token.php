<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC;

use Spiral\Auth\TokenInterface;

final readonly class Token implements TokenInterface
{
    public function __construct(
        private string $token,
    ) {}

    public function getID(): string
    {
        return $this->token;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return null;
    }

    public function getPayload(): array
    {
        return [];
    }
}
