<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC\Service;

interface ServiceRepositoryInterface
{
    public function getService(): Service;
}
