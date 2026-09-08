<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Event\RecordedEvent;
use Backslash\EventStore\Query\Query;

class TestInspector implements InspectorInterface
{
    public function getQuery(): Query
    {
        return new Query();
    }

    public function inspect(RecordedEvent $recordedEvent): void
    {
    }
}
