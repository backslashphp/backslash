<?php

declare(strict_types=1);

namespace Backslash\EventStoreReductionInspection;

use Backslash\Domain\RecordedEvent;
use Backslash\EventStore\Query\QueryInterface;

class TestReducer implements ReductionInspectorInterface
{
    private int $eventCount = 0;

    public function getQuery(): ?QueryInterface
    {
        return null;
    }

    public function inspect(RecordedEvent $recordedEvent): void
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
