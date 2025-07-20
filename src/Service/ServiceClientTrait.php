<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC\Service;

use Google\Protobuf\Internal\Message;
use Internal\Shared\gRPC\Exception\GrpcExceptionMapper;
use Spiral\Core\CoreInterface;
use Spiral\RoadRunner\GRPC\ContextInterface;
use Spiral\RoadRunner\GRPC\ServiceInterface;
use Spiral\RoadRunner\GRPC\StatusCode;

trait ServiceClientTrait
{
    private readonly array $registeredServices;

    public function __construct(
        private readonly CoreInterface $core,
        private readonly GrpcExceptionMapper $mapper,
        ServiceLocatorInterface $locator = new NullServiceLocator(),
    ) {
        $this->registeredServices = \array_map(
            static fn(ServiceInterface $service): string => $service::NAME,
            $locator->getServices(),
        );
    }

    private function isRegistered(): bool
    {
        return \in_array($this::NAME, $this->registeredServices, true);
    }

    /**
     * @throws \ReflectionException
     * @throws \Throwable
     */
    private function callAction(string $action, ContextInterface $ctx, Message $in): Message
    {
        if ($this->isRegistered()) {
            throw new \LogicException(\sprintf(
                'Infinite call of action "%s/%s" detected: Service is attempting to call itself, leading to a potential infinite loop.',
                $this::NAME,
                $action,
            ));
        }

        $method = new \ReflectionMethod($this, $action);
        $returnType = $method->getReturnType()->getName();

        $uri = '/' . $this::NAME . '/' . $action;

        [$response, $status] = $this->core->callAction($this::class, $uri, [
            'in' => $in,
            'ctx' => $ctx,
            'responseClass' => $returnType,
        ]);

        $code = $status->code ?? StatusCode::UNKNOWN;

        if ($code !== StatusCode::OK) {
            throw $this->mapper->fromError($status);
        }

        \assert($response instanceof $returnType);

        return $response;
    }
}
