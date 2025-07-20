<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC;

use CuyZ\Valinor\Mapper\TreeMapper;
use Google\Protobuf\Internal\MapField;
use Google\Protobuf\Internal\Message;
use Google\Protobuf\Internal\RepeatedField;

/**
 * @template TDto of object
 * @template TMessage of Message
 * @implements MapperInterface<TDto, TMessage>
 */
abstract readonly class AbstractMapper implements MapperInterface
{
    public function __construct(
        protected TreeMapper $treeMapper,
        protected CommandMapper $mapper,
    ) {}

    public function toMessage(object $object, string $message): Message
    {
        /** @var TMessage $message */
        $message = new $message();

        $json = \json_encode($object);
        // We don't need to set the message if the object is empty
        if ($json === '[]') {
            return $message;
        }

        $message->mergeFromJsonString($json, true);

        return $message;
    }

    protected function mapValue(mixed $value): mixed
    {
        if ($value instanceof Message) {
            return $this->mapper->fromMessage($value);
        } elseif ($value instanceof RepeatedField) {
            return \array_map(
                fn(mixed $message): mixed => $this->mapValue($message),
                \iterator_to_array($value, false),
            );
        } elseif ($value instanceof MapField) {
            return \array_map(
                fn(mixed $message): mixed => $this->mapValue($message),
                \iterator_to_array($value),
            );
        }

        return $value === '' ? null : $value;
    }
}
