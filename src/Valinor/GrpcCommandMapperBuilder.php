<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC\Valinor;

use CuyZ\Valinor\Mapper\TreeMapper;
use CuyZ\Valinor\MapperBuilder;
use Psr\SimpleCache\CacheInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spiral\Core\Attribute\Singleton;

#[Singleton]
final readonly class GrpcCommandMapperBuilder
{
    public function __construct(
        private ?CacheInterface $cache,
    ) {}

    public function build(): TreeMapper
    {
        $builder = (new MapperBuilder())
            ->infer(UuidInterface::class, static fn() => Uuid::class)
            ->registerConstructor(Uuid::class, Uuid::fromString(...))
            ->enableFlexibleCasting()
            ->allowPermissiveTypes();

        if ($this->cache) {
            $builder = $builder->withCache($this->cache);
        }

        return $builder->mapper();
    }
}
