<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC\Interceptor\Outgoing;

use Google\Protobuf\Internal\Message;
use Internal\Shared\gRPC\RequestContext;
use Internal\Shared\gRPC\Security\AuthenticatedUser;
use Spiral\Auth\AuthContextInterface;
use Spiral\Core\Container;
use Spiral\Core\CoreInterceptorInterface;
use Spiral\Core\CoreInterface;
use Spiral\Exceptions\ExceptionReporterInterface;

final readonly class TokenExtractorInterceptor implements CoreInterceptorInterface
{
    public function __construct(
        private Container $container,
        private ExceptionReporterInterface $reporter,
    ) {}

    public function process(string $controller, string $action, array $parameters, CoreInterface $core): mixed
    {
        $message = $parameters['in'];
        \assert($message instanceof Message);
        \assert($parameters['ctx'] instanceof RequestContext);

        // todo: добавить заглушку
        if (!$this->container->has(AuthContextInterface::class)) {
            return $core->callAction($controller, $action, $parameters);
        }

        try {
            /** @var AuthContextInterface $context */
            $context = $this->container->get(AuthContextInterface::class);

            $token = $context->getToken();
            /** @var AuthenticatedUser $user */
            $user = $context->getActor();

            if ($token !== null) {
                $parameters['ctx'] = $parameters['ctx']->withToken($token->getID());
            }

            if ($user !== null) {
                $parameters['ctx'] = $parameters['ctx']
                    ->withUser($user);
            }
        } catch (\Throwable $e) {
            $this->reporter->report($e);
        }

        return $core->callAction($controller, $action, $parameters);
    }
}
