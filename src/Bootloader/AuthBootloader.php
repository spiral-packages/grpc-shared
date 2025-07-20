<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC\Bootloader;

use Internal\Shared\gRPC\Security\AuthService;
use Internal\Shared\gRPC\Security\AuthServiceInterface;
use Spiral\Boot\Bootloader\Bootloader;

final class AuthBootloader extends Bootloader
{
    public function defineSingletons(): array
    {
        return [
            AuthServiceInterface::class => AuthService::class,
        ];
    }
}
