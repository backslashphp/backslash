<?php

declare(strict_types=1);

namespace Backslash\EventBus;

use Backslash\Aggregate\RecordedEvent;

interface EventHandlerInterface
{
    public function handle(string $aggregateId, RecordedEvent $recordedEvent): void;
}
