<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC\Interceptor\Incoming;

use Google\Protobuf\Internal\Message;
use Internal\Shared\gRPC\RequestContext;
use Sentry\Options;
use Spiral\Core\Attribute\Singleton;
use Spiral\Core\CoreInterceptorInterface;
use Spiral\Core\CoreInterface;
use Spiral\Debug\StateCollectorInterface;
use Spiral\Debug\StateInterface;
use Spiral\Logger\Event\LogEvent;
use Spiral\RoadRunner\GRPC\Context;
use Spiral\RoadRunner\GRPC\ResponseHeaders;

#[Singleton]
final class RequestCollectorInterceptor implements CoreInterceptorInterface, StateCollectorInterface
{
    private ?array $context = null;

    public function __construct(
        private readonly Options $options,
    ) {}

    public function process(string $controller, string $action, array $parameters, CoreInterface $core): mixed
    {
        $this->context = [
            'controller' => $controller,
            'action' => $action,
            'parameters' => $parameters,
        ];

        return $core->callAction($controller, $action, $parameters);
    }

    public function populate(StateInterface $state): void
    {
        if ($this->context === null) {
            return;
        }

        \assert($this->context['parameters']['ctx'] instanceof Context);
        \assert($this->context['parameters']['message'] instanceof Message);

        /** @var ResponseHeaders $headers */
        $headers = $this->context['parameters']['ctx']->getValue(ResponseHeaders::class, new ResponseHeaders());
        $payload = $this->context['parameters']['message']->serializeToJsonString();

        $data = [
            'service' => $this->context['controller'],
            'action' => $this->context['action'],
            'headers' => \iterator_to_array($headers->getIterator()),
            'values' => $this->context['parameters']['ctx']->getValues(),
        ];

        if ($this->options->shouldSendDefaultPii()) {
            $data['payload'] = \json_decode((string) $payload, true);
        } else {
            unset(
                $data['headers'][RequestContext::METADATA_USER_UUID],
                $data['headers'][RequestContext::METADATA_TOKEN],
            );
        }

        $state->addLogEvent(
            new LogEvent(
                time: new \DateTimeImmutable(),
                channel: 'gRPC',
                level: 'debug',
                message: 'Request',
                context: $data,
            ),
        );
    }

    public function reset(): void
    {
        $this->context = null;
    }
}
