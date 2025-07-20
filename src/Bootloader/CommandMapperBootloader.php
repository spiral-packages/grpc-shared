<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC\Bootloader;

use CuyZ\Valinor\Cache\FileSystemCache;
use CuyZ\Valinor\Mapper\TreeMapper;
use Internal\Shared\gRPC\CommandMapper;
use Internal\Shared\gRPC\Exception\GrpcExceptionMapper;
use Internal\Shared\gRPC\Valinor\GrpcCommandMapperBuilder;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Boot\DirectoriesInterface;
use Spiral\Boot\Environment\AppEnvironment;
use Spiral\Tokenizer\TokenizerListenerRegistryInterface;

/**
 * This class is read only. Please do not edit it directly.
 */
final class CommandMapperBootloader extends Bootloader
{
    public function defineSingletons(): array
    {
        return [
            TreeMapper::class => self::createTreeMapper(...),
            CommandMapper::class => CommandMapper::class,
            GrpcExceptionMapper::class => GrpcExceptionMapper::class,
            GrpcCommandMapperBuilder::class => static fn(
                DirectoriesInterface $dirs,
                AppEnvironment $env,
            ) => new GrpcCommandMapperBuilder(
                cache: match ($env) {
                    AppEnvironment::Production => new FileSystemCache(
                        cacheDir: $dirs->get('runtime') . 'cache/valinor',
                    ),
                    default => null,
                },
            ),
        ];
    }

    public function init(
        TokenizerListenerRegistryInterface $listenerRegistry,
        CommandMapper $mapperRegistry,
        GrpcExceptionMapper $exceptionMapperRegistry,
    ): void {
        $listenerRegistry->addListener($mapperRegistry);
        $listenerRegistry->addListener($exceptionMapperRegistry);
    }

    public function createTreeMapper(GrpcCommandMapperBuilder $builder): TreeMapper
    {
        return $builder->build();
    }
}
