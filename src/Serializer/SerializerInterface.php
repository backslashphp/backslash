<?php

declare(strict_types=1);

namespace Backslash\Serializer;

interface SerializerInterface
{
    /** @throws SerializationException */
    public function serialize(mixed $value): string;

    /** @throws DeserializationException */
    public function deserialize(string $payload, ?string $type = null): mixed;
}
