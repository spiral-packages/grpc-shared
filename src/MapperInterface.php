<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC;

use Google\Protobuf\Internal\Message;

/**
 * @template TDto of object
 * @template TMessage of Message
 */
interface MapperInterface
{
    /**
     * @param TMessage $message
     * @param class-string<TDto> $class
     * @return TDto
     */
    public function fromMessage(Message $message, string $class): object;

    /**
     * @param TDto $object
     * @param class-string<TMessage> $message
     * @return TMessage
     */
    public function toMessage(object $object, string $message): Message;
}
