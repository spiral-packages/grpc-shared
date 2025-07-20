<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC\Interceptor\Incoming;

use Internal\Shared\gRPC\RequestContext;
use Spiral\Core\Container;
use Spiral\Core\CoreInterceptorInterface;
use Spiral\Core\CoreInterface;
use Spiral\RoadRunner\GRPC\ContextInterface;
use Spiral\RoadRunner\GRPC\ServiceInterface;

final readonly class ContextInterceptor implements CoreInterceptorInterface
{
    public function __construct(
        private Container $container,
    ) {}

    /**
     * Convert internal context to Request context.
     *
     * @param array{service: ServiceInterface, ctx: ContextInterface, input: string} $parameters
     */
    public function process(string $controller, string $action, array $parameters, CoreInterface $core): mixed
    {
        $parameters['ctx'] = new RequestContext($parameters['ctx']->getValues());

        return $this->container->runScope([
            ContextInterface::class => $parameters['ctx'],
        ], static fn() => $core->callAction($controller, $action, $parameters));
    }
}
