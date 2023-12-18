<?php

declare(strict_types=1);

namespace Backslash\EventStoreReductionInspection;

use Backslash\Aggregate\RecordedEvent;
use Backslash\EventStore\Filter;

class TestReducer implements ReductionInspectorInterface
{
    private int $eventCount = 0;

    public function getFilter(): Filter
    {
        return new Filter();
    }

    public function inspect(string $aggregateId, string $aggregateType, RecordedEvent $recordedEvent): void
    {
        $this->eventCount++;
    }

    public function getResult(): mixed
    {
        return $this->eventCount;
    }

    public function reset(): void
    {
        $this->eventCount = 0;
    }
}
