<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC\Exception;

use Internal\Shared\gRPC\Services\Common\v1\DTO\Exception;
use Internal\Shared\gRPC\Services\Common\v1\DTO\ValidationException;
use Spiral\RoadRunner\GRPC\Exception\GRPCExceptionInterface;

interface MapperInterface
{
    public function toGrpcException(\Throwable $e): GRPCExceptionInterface;

    public function fromError(Exception|ValidationException $error): \Throwable;
}
