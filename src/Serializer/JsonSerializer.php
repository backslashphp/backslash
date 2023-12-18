<?php

declare(strict_types=1);

namespace Backslash\Serializer;

final class JsonSerializer implements AdapterInterface
{
    private int $flags;

    public function __construct(int $flags = JSON_FORCE_OBJECT)
    {
        $this->flags = $flags;
    }

    public function serialize(mixed $value): string
    {
        $json = json_encode($value, $this->flags);
        if (json_last_error()) {
            throw new SerializationException(json_last_error_msg());
        }
        return $json;
    }

    public function deserialize(string $payload, ?string $type = null): mixed
    {
        $value = json_decode($payload, true);
        if (json_last_error()) {
            throw new DeserializationException(json_last_error_msg());
        }
        return $value;
    }
}
