<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC\Interceptor\Incoming;

use Internal\Shared\gRPC\Exception\GrpcExceptionMapper;
use Spiral\Core\CoreInterceptorInterface;
use Spiral\Core\CoreInterface;
use Spiral\Exceptions\ExceptionHandlerInterface;
use Spiral\RoadRunner\GRPC\Exception\GRPCExceptionInterface;

final readonly class ExceptionHandlerInterceptor implements CoreInterceptorInterface
{
    public function __construct(
        private ExceptionHandlerInterface $errorHandler,
        private GrpcExceptionMapper $mapper,
    ) {}

    /**
     * Handle exceptions.
     * @throws GRPCExceptionInterface
     */
    public function process(string $controller, string $action, array $parameters, CoreInterface $core): mixed
    {
        try {
            return $core->callAction($controller, $action, $parameters);
        } catch (\Throwable $e) {
            if (!$e instanceof \DomainException) {
                $this->errorHandler->report($e);
            }

            throw $this->mapper->toGrpcException($e);
        }
    }
}
