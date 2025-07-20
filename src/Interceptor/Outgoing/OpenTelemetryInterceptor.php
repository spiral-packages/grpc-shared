<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC\Interceptor\Outgoing;

use Internal\Shared\gRPC\RequestContext;
use Psr\Container\ContainerInterface;
use Spiral\Core\CoreInterceptorInterface;
use Spiral\Core\CoreInterface;
use Spiral\Telemetry\TraceKind;
use Spiral\Telemetry\TracerInterface;

final readonly class OpenTelemetryInterceptor implements CoreInterceptorInterface
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function process(string $controller, string $action, array $parameters, CoreInterface $core): mixed
    {
        $tracer = $this->container->get(TracerInterface::class);
        \assert($tracer instanceof TracerInterface);

        if (isset($parameters['ctx']) and $parameters['ctx'] instanceof RequestContext) {
            $parameters['ctx'] = $parameters['ctx']->withTelemetry($tracer->getContext());
        }

        return $tracer->trace(
            name: \sprintf('GRPC request %s', $action),
            callback: static fn() => $core->callAction($controller, $action, $parameters),
            attributes: \compact('controller', 'action'),
            traceKind: TraceKind::PRODUCER,
        );
    }
}
