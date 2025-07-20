<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC\Security;

use Internal\Shared\gRPC\Command\Auth\v1\DTO\User as UserV1;
use Internal\Shared\gRPC\Command\Auth\v2\DTO\User as UserV2;
use Spiral\RoadRunner\GRPC\Exception\UnauthenticatedException;

interface AuthServiceInterface
{
    public function isAuthenticated(): bool;

    /**
     * @throws UnauthenticatedException
     */
    public function getUserOrThrowException(): UserV1|UserV2;

    public function getUser(): UserV1|UserV2|null;

    public function hasToken(): bool;

    public function getToken(): ?string;

    /**
     * @throws UnauthenticatedException
     */
    public function getTokenOrThrowException(): string;
}
