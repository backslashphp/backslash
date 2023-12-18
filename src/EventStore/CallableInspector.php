<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Aggregate\RecordedEvent;

final class CallableInspector implements InspectorInterface
{
    /** @var callable */
    private $callable;

    private Filter $filter;

    public function __construct(callable $callable, Filter $filter)
    {
        $this->callable = $callable;
        $this->filter = $filter;
    }

    public function getFilter(): Filter
    {
        return $this->filter;
    }

    public function inspect(string $aggregateId, string $aggregateType, RecordedEvent $recordedEvent): void
    {
        $callable = $this->callable;
        $callable($aggregateId, $recordedEvent);
    }
}
