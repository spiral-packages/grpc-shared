<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC\Interceptor\Incoming;

use Internal\Shared\gRPC\RequestContext;
use Spiral\Core\CoreInterceptorInterface;
use Spiral\Core\CoreInterface;
use Spiral\Telemetry\TraceKind;
use Spiral\Telemetry\TracerFactoryInterface;

final readonly class OpenTelemetryInterceptor implements CoreInterceptorInterface
{
    public function __construct(
        private TracerFactoryInterface $tracerFactory,
    ) {}

    public function process(string $controller, string $action, array $parameters, CoreInterface $core): mixed
    {
        $ctx = [];

        if (isset($parameters['ctx']) and $parameters['ctx'] instanceof RequestContext) {
            $ctx = $parameters['ctx']->getTelemetry();
        }

        return $this->tracerFactory->make($ctx)->trace(
            name: \sprintf('Interceptor [%s]', self::class),
            callback: static fn(): mixed => $core->callAction($controller, $action, $parameters),
            attributes: [
                'controller' => $controller,
                'action' => $action,
            ],
            scoped: true,
            traceKind: TraceKind::SERVER,
        );
    }
}
