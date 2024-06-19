<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Domain\RecordedEvent;
use Backslash\EventStore\Query\QueryInterface;

class TestInspector implements InspectorInterface
{
    public function getQuery(): ?QueryInterface
    {
        return null;
    }

    public function inspect(RecordedEvent $recordedEvent): void
    {
    }
}
