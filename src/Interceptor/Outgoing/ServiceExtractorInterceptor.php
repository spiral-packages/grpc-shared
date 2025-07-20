<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC\Interceptor\Outgoing;

use Internal\Shared\gRPC\RequestContext;
use Internal\Shared\gRPC\Service\ServiceRepositoryInterface;
use Spiral\Core\CoreInterceptorInterface;
use Spiral\Core\CoreInterface;

final readonly class ServiceExtractorInterceptor implements CoreInterceptorInterface
{
    public function __construct(
        private ServiceRepositoryInterface $env,
    ) {}

    public function process(string $controller, string $action, array $parameters, CoreInterface $core): mixed
    {
        $context = $parameters['ctx'];
        \assert($context instanceof RequestContext);

        $parameters['ctx'] = $context
            ->withService($this->env->getService());

        return $core->callAction($controller, $action, $parameters);
    }
}
