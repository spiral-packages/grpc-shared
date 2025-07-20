<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC;

use Google\Protobuf\Internal\Message;
use Internal\CQRS\CommandBusInterface;
use Internal\CQRS\Exception\CommandNotRegisteredException;
use Internal\Shared\gRPC\Services\Auth\v1\DTO\User;
use Spiral\RoadRunner\GRPC\ContextInterface;

trait ServiceServerTrait
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly CommandMapper $mapper,
    ) {}

    /**
     * @throws \Throwable
     * @throws CommandNotRegisteredException
     */
    private function callAction(string $action, ContextInterface $ctx, Message $in): Message
    {
        if (($user = $ctx->getValue(User::class)) !== null && \method_exists($in, 'setUser')) {
            $in->setUser($user);
        }

        $command = $this->mapper->fromMessage($in);

        return $this->mapper->toMessage(
            $this->commandBus->dispatch($command),
        );
    }
}
