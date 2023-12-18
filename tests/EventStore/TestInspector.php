<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Aggregate\RecordedEvent;

class TestInspector implements InspectorInterface
{
    /** @var RecordedEvent[] */
    private array $inspectedEvents = [];

    public function getFilter(): Filter
    {
        return new Filter();
    }

    public function inspect(string $aggregateId, string $aggregateType, RecordedEvent $recordedEvent): void
    {
        $this->inspectedEvents[] = $recordedEvent;
    }

    public function getInspectedEvents(): array
    {
        return $this->inspectedEvents;
    }
}
