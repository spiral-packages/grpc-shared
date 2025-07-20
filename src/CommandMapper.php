<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC;

use CuyZ\Valinor\Mapper\MappingError;
use Google\Protobuf\Internal\Message;
use Internal\Shared\gRPC\Attribute\Mapper;
use Spiral\Attributes\ReaderInterface;
use Spiral\Core\Attribute\Singleton;
use Spiral\Core\FactoryInterface;
use Spiral\Exceptions\ExceptionReporterInterface;
use Spiral\Tokenizer\Attribute\TargetAttribute;
use Spiral\Tokenizer\TokenizationListenerInterface;

#[TargetAttribute(attribute: Mapper::class)]
#[Singleton]
final class CommandMapper implements TokenizationListenerInterface
{
    /** @var array<class-string, class-string<MapperInterface>> */
    private array $mappers = [];

    private array $classMap = [];

    /** @var array<class-string, MapperInterface> */
    private array $resolvedMappers = [];

    public function __construct(
        private readonly ReaderInterface $reader,
        private readonly FactoryInterface $factory,
        private readonly ExceptionReporterInterface $reporter,
    ) {}

    public function hasMapper(object $object): bool
    {
        return isset($this->classMap[$object::class]);
    }

    public function toMessage(object $object): Message
    {
        if (!$this->hasMapper($object)) {
            throw new \InvalidArgumentException('Mapper not found');
        }

        $message = $this->classMap[$object::class];

        if (!isset($this->resolvedMappers[$object::class])) {
            $this->resolvedMappers[$object::class] = $this->factory->make($this->mappers[$object::class]);
        }
        $mapper = $this->resolvedMappers[$object::class];

        return $mapper->toMessage($object, $message);
    }

    public function fromMessage(Message $message): object
    {
        if (!isset($this->classMap[$message::class])) {
            throw new \InvalidArgumentException(\sprintf('Mapper not found for %s', $message::class));
        }

        $class = $this->classMap[$message::class];
        if (!isset($this->resolvedMappers[$class])) {
            $this->resolvedMappers[$class] = $this->factory->make($this->mappers[$class]);
        }
        $mapper = $this->resolvedMappers[$class];

        try {
            return $mapper->fromMessage($message, $class);
        } catch (MappingError $error) {
            $this->reporter->report($error);
        }
    }

    public function listen(\ReflectionClass $class): void
    {
        $this->register($class->getName());
    }

    /**
     * @param class-string<MapperInterface> $mapperClass
     *
     * @throws \ReflectionException
     */
    public function register(string $mapperClass): void
    {
        $class = new \ReflectionClass($mapperClass);
        $mapper = $this->reader->firstClassMetadata($class, Mapper::class);

        $this->classMap[$mapper->class] = $mapper->messageClass;
        $this->classMap[$mapper->messageClass] = $mapper->class;

        $this->mappers[$mapper->class] = $class->getName();
    }

    public function finalize(): void
    {
        // do nothing
    }
}
