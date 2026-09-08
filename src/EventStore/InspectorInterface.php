<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Event\RecordedEvent;
use Backslash\EventStore\Query\Query;

interface InspectorInterface
{
    public function getQuery(): Query;

    public function inspect(RecordedEvent $recordedEvent): void;
}
