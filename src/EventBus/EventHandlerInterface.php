<?php

declare(strict_types=1);

namespace Backslash\EventBus;

use Backslash\Event\RecordedEvent;

interface EventHandlerInterface
{
    public function handle(RecordedEvent $recordedEvent): void;
}
