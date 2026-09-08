<?php

declare(strict_types=1);

namespace Backslash\PdoEventStore;

use Backslash\Event\RecordedEvent;
use Backslash\EventStore\InspectorInterface;
use Backslash\EventStore\Query\Query;

class TestInspector implements InspectorInterface
{
    /** @var RecordedEvent[] */
    private array $inspectedEvents = [];

    private Query $query;

    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    public function getQuery(): Query
    {
        return $this->query;
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
