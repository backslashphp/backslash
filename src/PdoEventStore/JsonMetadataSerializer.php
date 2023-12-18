<?php

declare(strict_types=1);

namespace Backslash\PdoEventStore;

use Backslash\Aggregate\Metadata;
use Backslash\Serializer\AdapterInterface;
use Backslash\Serializer\DeserializationException;
use InvalidArgumentException;

class JsonMetadataSerializer implements AdapterInterface
{
    public function serialize(mixed $value): string
    {
        if (!$value instanceof Metadata) {
            throw new InvalidArgumentException();
        }
        return json_encode($value->toArray());
    }

    public function deserialize(string $payload, ?string $type = null): mixed
    {
        $array = json_decode($payload, true);
        if (json_last_error()) {
            throw new DeserializationException();
        }
        return new Metadata($array);
    }
}
