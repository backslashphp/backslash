<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Aggregate\RecordedEvent;

interface InspectorInterface
{
    public function getFilter(): Filter;

    public function inspect(string $aggregateId, string $aggregateType, RecordedEvent $recordedEvent): void;
}
