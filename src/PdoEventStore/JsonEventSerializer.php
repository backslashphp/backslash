<?php

declare(strict_types=1);

namespace Backslash\PdoEventStore;

use Backslash\Event\EventInterface;
use Backslash\EventNameResolver\EventNameResolverInterface;
use Backslash\Serializer\AdapterInterface;
use Backslash\Serializer\DeserializationException;
use InvalidArgumentException;

class JsonEventSerializer implements AdapterInterface
{
    private EventNameResolverInterface $eventNameResolver;

    public function __construct(EventNameResolverInterface $eventNameResolver)
    {
        $this->eventNameResolver = $eventNameResolver;
    }

    public function serialize(mixed $value): string
    {
        if (!$value instanceof EventInterface) {
            throw new InvalidArgumentException();
        }
        if (empty($value->toArray())) {
            return '{}';
        }
        return json_encode($value->toArray());
    }

    public function deserialize(string $payload, ?string $type = null): mixed
    {
        if (is_null($type)) {
            throw new InvalidArgumentException('Type must be provided.');
        }
        $array = json_decode($payload, true);
        if (json_last_error()) {
            throw new DeserializationException();
        }
        $fqcn = $this->eventNameResolver->resolveClass($type);
        /** @var EventInterface $fqcn */
        return $fqcn::fromArray($array);
    }
}
