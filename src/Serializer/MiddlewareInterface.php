<?php

declare(strict_types=1);

namespace Backslash\Serializer;

interface MiddlewareInterface
{
    /** @throws SerializationException */
    public function serialize(mixed $value, SerializerInterface $next): string;

    /** @throws DeserializationException */
    public function deserialize(string $payload, ?string $type, SerializerInterface $next): mixed;
}
