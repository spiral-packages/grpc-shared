<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC\Exception;

use Spiral\RoadRunner\GRPC\Exception\GRPCException;
use Spiral\RoadRunner\GRPC\StatusCode;

final class ConnectionException extends GRPCException
{
    protected const CODE = StatusCode::UNAVAILABLE;

    public static function createFromStatus(\stdClass $status): self
    {
        return new self(
            message: $status->details,
            code: $status->code,
            details: $status->metadata,
        );
    }
}
