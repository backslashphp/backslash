<?php

declare(strict_types=1);

namespace Backslash\EventStore;

final class StreamNotFoundException extends EventStoreException
{
    public static function forId(string $aggregateId, string $aggregateType): self
    {
        $message = sprintf('No stream found for aggregate of type [%s] and ID [%s].', $aggregateType, $aggregateId);
        return new self($message);
    }
}
