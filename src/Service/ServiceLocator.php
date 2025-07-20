<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC\Service;

use Internal\Shared\gRPC\Attribute\ServiceClient;
use Psr\Container\ContainerInterface;
use Spiral\Core\Attribute\Singleton;
use Spiral\Core\Exception\Container\ContainerException;
use Spiral\RoadRunner\GRPC\ServiceInterface;
use Spiral\RoadRunnerBridge\GRPC\LocatorInterface;
use Spiral\Tokenizer\Attribute\TargetClass;
use Spiral\Tokenizer\TokenizationListenerInterface;

#[TargetClass(ServiceInterface::class)]
#[Singleton]
final class ServiceLocator implements
    ServiceLocatorInterface,
    LocatorInterface,
    TokenizationListenerInterface
{
    private array $services = [];

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public function getServices(): array
    {
        return $this->services;
    }

    public function listen(\ReflectionClass $class): void
    {
        if (!$class->isInstantiable()) {
            return;
        }

        if ($class->getAttributes(ServiceClient::class) !== []) {
            return;
        }

        try {
            $instance = $this->container->get($class->getName());
        } catch (ContainerException) {
            return;
        }

        foreach ($class->getInterfaces() as $interface) {
            if ($interface->getName() === ServiceInterface::class) {
                continue;
            }

            if ($class->isSubclassOf(ServiceInterface::class)) {
                $this->services[$interface->getName()] = $instance;
            }
        }
    }

    public function finalize(): void {}
}
