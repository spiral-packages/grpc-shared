<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC\Service;

use Google\Protobuf\Internal\Message;
use Google\Rpc\Status;
use Grpc\BaseStub;
use Spiral\Core\CoreInterface;
use Spiral\RoadRunner\GRPC\ContextInterface;

final class GrpcClientCore extends BaseStub implements CoreInterface
{
    /**
     * @return array{0: Message, 1: Status}
     */
    public function callAction(string $controller, string $action, array $parameters = []): array
    {
        /** @var ContextInterface $ctx */
        $ctx = $parameters['ctx'];

        return $this->_simpleRequest(
            $action,
            $parameters['in'],
            [$parameters['responseClass'], 'decode'],
            (array) $ctx->getValue('metadata'),
            (array) $ctx->getValue('options'),
        )->wait();
    }
}
