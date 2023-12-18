<?php

declare(strict_types=1);

namespace Backslash\Serializer;

final class SerializeFunctionSerializer implements AdapterInterface
{
    public function serialize(mixed $value): string
    {
        return serialize($value);
    }

    public function deserialize(string $payload, ?string $type = null): mixed
    {
        return unserialize($payload);
    }
}
