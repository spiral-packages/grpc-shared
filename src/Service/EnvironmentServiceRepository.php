<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC\Service;

use Spiral\Boot\Environment;
use Spiral\Boot\EnvironmentInterface;

final readonly class EnvironmentServiceRepository implements ServiceRepositoryInterface
{
    public function __construct(
        private EnvironmentInterface $env = new Environment(),
    ) {}

    public function getService(): Service
    {
        return new Service(
            service: (string) $this->env->get('OTEL_SERVICE_NAME'),
            version: (string) $this->env->get('OTEL_SERVICE_VERSION'),
        );
    }
}
