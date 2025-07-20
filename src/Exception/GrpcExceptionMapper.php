<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC\Exception;

use Google\Rpc\Status;
use Internal\Shared\gRPC\Attribute\ErrorMapper;
use Internal\Shared\gRPC\Service\EnvironmentServiceRepository;
use Internal\Shared\gRPC\Service\ServiceRepositoryInterface;
use Internal\Shared\gRPC\Services\Common\v1\DTO\Exception;
use Internal\Shared\gRPC\Services\Common\v1\DTO\Service;
use Internal\Shared\gRPC\Services\Common\v1\DTO\ValidationException;
use Spiral\Attributes\AttributeReader;
use Spiral\Attributes\ReaderInterface;
use Spiral\Core\Attribute\Singleton;
use Spiral\Core\Container;
use Spiral\Core\FactoryInterface;
use Spiral\RoadRunner\GRPC\Exception\GRPCException;
use Spiral\RoadRunner\GRPC\Exception\GRPCExceptionInterface;
use Spiral\RoadRunner\GRPC\StatusCode;
use Spiral\Tokenizer\Attribute\TargetAttribute;
use Spiral\Tokenizer\TokenizationListenerInterface;

#[TargetAttribute(attribute: ErrorMapper::class)]
#[Singleton]
final class GrpcExceptionMapper implements TokenizationListenerInterface
{
    /** @var array<class-string, class-string<MapperInterface>> */
    private array $mappers = [];

    /** @var array<class-string, MapperInterface> */
    private array $resolvedMappers = [];

    public function __construct(
        private readonly ReaderInterface $reader = new AttributeReader(),
        private readonly FactoryInterface $factory = new Container(),
        private readonly ServiceRepositoryInterface $service = new EnvironmentServiceRepository(),
    ) {
        new Exception();
        new ValidationException();
    }

    public function listen(\ReflectionClass $class): void
    {
        /** @var ErrorMapper $mapper */
        $mapper = $this->reader->firstClassMetadata($class, ErrorMapper::class);

        $this->mappers[$mapper->type] = $class->getName();
    }

    public function finalize(): void
    {
        // do nothing
    }

    public function toGrpcException(\Throwable $e): GRPCExceptionInterface
    {
        $type = $this->getExceptionKey($e);
        if (isset($this->mappers[$type])) {
            $mapper = $this->resolvedMappers[$type] ??= $this->factory->make($this->mappers[$type]);
            return $mapper->toGrpcException($e);
        }

        $info = $this->makeExceptionMessageObject($e);

        $previous = $e->getPrevious();
        while ($previous !== null) {
            $info->setPrevious($this->makeExceptionMessageObject($previous));
            $previous = $previous->getPrevious();
        }

        return new GRPCException(
            message: $e->getMessage(),
            code: $e->getCode(),
            details: [$info],
            previous: $e,
        );
    }

    public function fromError(object $error): \Throwable
    {
        if (!isset($error->metadata['grpc-status-details-bin'])) {
            return match ($error->code) {
                StatusCode::UNAVAILABLE => ConnectionException::createFromStatus($error),
                default => ResponseException::createFromStatus($error),
            };
        }

        $exception = $this->parseException($error);

        if (!isset($this->mappers[$exception->getType()])) {
            return ResponseException::createFromStatus($error);
        }

        return $this->fromException($exception);
    }

    public function fromException(Exception|ValidationException $exception): \Throwable
    {
        if (!isset($this->mappers[$exception->getType()])) {
            return new ResponseException(
                message: $exception->getMessage(),
                code: (int) $exception->getCode(),
            );
        }

        $mapper = $this->resolvedMappers[$exception->getType()] ??= $this->factory->make(
            $this->mappers[$exception->getType()],
        );

        return $mapper->fromError($exception);
    }

    private function makeExceptionMessageObject(\Throwable $e): Exception
    {
        $service = $this->service->getService();

        return match (true) {
            default => new Exception([
                'type' => $this->getExceptionKey($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'service' => new Service([
                    'name' => $service->service,
                    'version' => $service->version,
                ]),
            ]),
        };
    }

    private function parseException(object $status): Exception|ValidationException
    {
        $status = \array_map(
            static function (string $info) {
                $status = new Status();
                $status->mergeFromString($info);

                return $status;
            },
            $status->metadata['grpc-status-details-bin'],
        )[0];

        return $status->getDetails()[0]->unpack();
    }

    private function getExceptionKey(\Throwable $e): string
    {
        $className = (new \ReflectionClass($e))->getShortName();
        return \strtolower((string) \preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
    }
}
