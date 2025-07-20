<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC\Security;

use Spiral\Auth\AuthContextInterface;
use Spiral\Core\Container;
use Spiral\RoadRunner\GRPC\Exception\UnauthenticatedException;

final readonly class AuthService implements AuthServiceInterface
{
    public function __construct(
        private Container $container,
    ) {}

    public function isAuthenticated(): bool
    {
        return $this->getUser() !== null;
    }

    public function getUserOrThrowException(): AuthenticatedUser
    {
        $user = $this->getUser();
        if ($user === null) {
            throw new UnauthenticatedException('user_not_found');
        }

        return $user;
    }

    public function getUser(): AuthenticatedUser|null
    {
        return $this->getAuthContext()->getActor();
    }

    /**
     * @internal
     * Use only in public endpoints
     */
    public function hasToken(): bool
    {
        return $this->getToken() !== null;
    }

    /**
     * @internal
     * Use only in public endpoints
     */
    public function getToken(): ?string
    {
        return $this->getAuthContext()->getToken()?->getID();
    }

    /**
     * @internal
     * Use only in public endpoints
     */
    public function getTokenOrThrowException(): string
    {
        $token = $this->getToken();
        if ($token === null) {
            throw new UnauthenticatedException('token_is_missing');
        }

        return $token;
    }

    private function getAuthContext(): AuthContextInterface
    {
        return $this->container->get(AuthContextInterface::class);
    }
}
