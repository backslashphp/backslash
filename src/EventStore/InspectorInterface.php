<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Event\RecordedEvent;
use Backslash\EventStore\Query\QueryInterface;

interface InspectorInterface
{
    public function getQuery(): ?QueryInterface;

    public function inspect(RecordedEvent $recordedEvent): void;
}
