<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC\Interceptor\Incoming;

use Internal\Shared\gRPC\RequestContext;
use Internal\Shared\gRPC\Token;
use Spiral\Auth\ActorProviderInterface;
use Spiral\Auth\AuthContext;
use Spiral\Auth\AuthContextInterface;
use Spiral\Auth\TokenInterface;
use Spiral\Core\Container;
use Spiral\Core\CoreInterceptorInterface;
use Spiral\Core\CoreInterface;
use Spiral\RoadRunner\GRPC\ServiceInterface;

final readonly class GuardInterceptor implements CoreInterceptorInterface
{
    public function __construct(
        private Container $container,
    ) {}

    /**
     * Check auth token for service methods with Guarded attribute.
     *
     * @param array{service: ServiceInterface, ctx: RequestContext, input: string} $parameters
     */
    public function process(string $controller, string $action, array $parameters, CoreInterface $core): mixed
    {
        $authContext = new AuthContext(
            $actorProvider = $this->createActorProvider(),
        );

        $token = $parameters['ctx']->getToken();

        if ($parameters['ctx']->hasUser()) {
            $actorProvider->setActor($parameters['ctx']->getUser());
        }

        if ($token !== null) {
            $authContext->start(
                token: new Token($token),
                transport: 'grpc',
            );
        }

        return $this->container->runScope([
            AuthContextInterface::class => $authContext,
        ], static fn() => $core->callAction($controller, $action, $parameters));
    }

    private function createActorProvider(): ActorProviderInterface
    {
        return new class implements ActorProviderInterface {
            private ?object $actor = null;

            public function setActor(object $actor): void
            {
                $this->actor = $actor;
            }

            public function getActor(TokenInterface $token): ?object
            {
                return $this->actor;
            }
        };
    }
}
