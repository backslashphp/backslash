<?php

declare(strict_types=1);

namespace Backslash\PdoEventStore;

use Backslash\Domain\RecordedEvent;
use Backslash\EventStore\InspectorInterface;
use Backslash\EventStore\Query\QueryInterface;

class TestInspector implements InspectorInterface
{
    /** @var RecordedEvent[] */
    private array $inspectedEvents = [];

    public function getQuery(): ?QueryInterface
    {
        return null;
    }

    public function inspect(RecordedEvent $recordedEvent): void
    {
        $this->inspectedEvents[] = $recordedEvent;
    }

    public function getInspectedEvents(): array
    {
        return $this->inspectedEvents;
    }
}
