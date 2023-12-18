<?php

declare(strict_types=1);

namespace Backslash\AggregateStore;

use Exception;

final class AggregateNotFoundException extends Exception
{
    public static function forAggregate(string $aggregateId, string $aggregateType, ?Exception $previous = null): self
    {
        $message = sprintf('Aggregate of type [%s] with ID [%s] not found.', $aggregateType, $aggregateId);
        return new self($message, 0, $previous);
    }
}
