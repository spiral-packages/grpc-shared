<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC\Service;

interface ServiceLocatorInterface
{
    public function getServices(): array;
}
