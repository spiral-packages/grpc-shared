<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC\Request;

use Psr\Container\ContainerInterface;
use Spiral\RoadRunner\GRPC\ContextInterface;

final readonly class RequestsContextScope implements ContextInterface
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function withValue(string $key, mixed $value): ContextInterface
    {
        throw new \RuntimeException('Not allowed to change context');
    }

    public function getValue(string $key): mixed
    {
        return $this->geContext()->getValue($key);
    }

    public function getValues(): array
    {
        return $this->geContext()->getValues();
    }

    private function geContext(): ContextInterface
    {
        return $this->container->get(ContextInterface::class);
    }
}
