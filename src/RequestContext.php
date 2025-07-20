<?php

declare(strict_types=1);

namespace Internal\Shared\gRPC;

use Internal\Shared\gRPC\Security\AuthenticatedUser;
use Internal\Shared\gRPC\Service\Service;
use Spiral\RoadRunner\GRPC\ContextInterface;
use Spiral\RoadRunner\GRPC\ResponseHeaders;

final class RequestContext implements ContextInterface
{
    public const METADATA_USER = 'user';
    public const METADATA_TOKEN = 'token';
    public const METADATA_EXTERNAL_REQUEST = 'external';
    public const METADATA_BREADCRUMBS = 'breadcrumbs';

    private array $values = [];

    /**
     * @param array<string, mixed> $values
     */
    public function __construct(
        array $values = [],
    ) {
        $this->setValues($values);
    }

    public function addBreadcrumb(string $service): self
    {
        $metadata = $this->getValue('metadata', []);
        $metadata[self::METADATA_BREADCRUMBS][] = $service;

        return $this->withMetadata($metadata);
    }

    public function getBreadcrumbs(): array
    {
        return $this->getValue('metadata', [])[self::METADATA_BREADCRUMBS] ?? [];
    }

    public function getInitialBreadcrumb(): string
    {
        return $this->getBreadcrumbs()[0] ?? '';
    }

    public function withTelemetry(?array $context): self
    {
        $metadata = $this->getValue('metadata', []);
        $metadata['telemetry'] = [\json_encode($context)];

        return $this->withMetadata($metadata);
    }

    public function getTelemetry(): array
    {
        $value = $this->getValue('metadata', [])['telemetry'][0] ?? null;

        if ($value !== null) {
            return (array) \json_decode((string) $value, true);
        }

        return [];
    }

    public function withService(Service $service): self
    {
        $metadata = $this->getValue('metadata', []);
        $metadata['service'] = [\json_encode([$service->service, $service->version])];

        return $this->withMetadata($metadata)->addBreadcrumb($service->service);
    }

    public function getService(): Service
    {
        $value = $this->getValue('metadata', [])['service'][0] ?? null;

        if ($value !== null) {
            [$service, $version] = \json_decode((string) $value, true);
            return new Service(
                service: $service,
                version: $version,
            );
        }

        return new Service(
            service: '',
            version: '',
        );
    }

    public function withUser(AuthenticatedUser $user, string $key = self::METADATA_USER): self
    {
        $metadata = $this->getValue('metadata', []);
        $metadata[$key] = [$user::class, \json_encode($user)];

        return $this->withMetadata($metadata);
    }

    public function getUser(): AuthenticatedUser|null
    {
        if (!$this->hasUser()) {
            return null;
        }

        [$class, $json] = $this->getValue('metadata', [])[self::METADATA_USER];

        $data = \json_decode((string) $json, true);

        return AuthenticatedUser::fromArray($data);
    }

    public function hasUser(): bool
    {
        $value = $this->getValue('metadata', [])[self::METADATA_USER][0] ?? null;

        return $value !== null;
    }

    /**
     * Store auth token to the metadata.
     */
    public function withToken(?string $token, string $key = self::METADATA_TOKEN): self
    {
        if ($token === null) {
            return $this;
        }

        $metadata = $this->getValue('metadata', []);
        $metadata[$key] = [$token];

        return $this->withMetadata($metadata);
    }

    /**
     * Get token from the metadata.
     */
    public function getToken(string $key = self::METADATA_TOKEN): ?string
    {
        return $this->getValue('metadata', [])[$key][0] ?? null;
    }

    /**
     * Mark current request as external.
     */
    public function markAsExternal(): self
    {
        $metadata = $this->getValue('metadata', []);
        $metadata[self::METADATA_EXTERNAL_REQUEST] = ['1'];

        return $this->withMetadata($metadata);
    }

    /**
     * Check if current request is internal.
     */
    public function isInternalRequest(): bool
    {
        $metadata = $this->getValue('metadata', []);
        if (empty($metadata)) {
            return true;
        }

        if (!\array_key_exists(self::METADATA_EXTERNAL_REQUEST, $metadata)) {
            return true;
        }

        $isExternal = (bool) ($metadata[self::METADATA_EXTERNAL_REQUEST][0] ?? '0');

        return !$isExternal;
    }

    /**
     * Check if current request is external.
     */
    public function isExternalRequest(): bool
    {
        return !$this->isInternalRequest();
    }

    /**
     * Set metadata to the context.
     */
    public function withMetadata(array $metadata): self
    {
        return $this->withValue('metadata', $metadata);
    }

    /**
     * Set options to the context.
     */
    public function withOptions(array $metadata): self
    {
        return $this->withValue('options', $metadata);
    }

    /**
     * Add value to the context.
     * @param mixed $value
     */
    public function withValue(string $key, $value): self
    {
        $ctx = clone $this;
        $ctx->values[$key] = $value;

        return $ctx;
    }

    /**
     * Get value from the context.
     * @param null|mixed $default
     */
    public function getValue(string $key, $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    /**
     * Get all values from the context.
     */
    public function getValues(): array
    {
        return $this->values;
    }

    private function setValues(array $values): void
    {
        $metadata = [];
        $system = [
            'grpc-accept-encoding',
            'content-type',
            'user-agent',
            ResponseHeaders::class,
        ];

        foreach ($values as $key => $value) {
            if (!\str_starts_with($key, ':') && !\in_array($key, $system)) {
                $metadata[$key] = $value;
                continue;
            }

            $this->values[$key] = $value;
        }

        $this->values['metadata'] = $metadata;
    }
}
