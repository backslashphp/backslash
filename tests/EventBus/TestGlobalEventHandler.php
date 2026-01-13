<?php

declare(strict_types=1);

namespace Backslash\EventBus;

use Backslash\Event\EventInterface;
use Backslash\Event\RecordedEvent;

class TestGlobalEventHandler implements EventHandlerInterface
{
    /** @var EventInterface[] */
    private array $handledEvents = [];

    public function handle(RecordedEvent $recordedEvent): void
    {
        $this->handledEvents[] = $recordedEvent->getEvent();
    }

    /** @return EventInterface[] */
    public function getHandledEvents(): array
    {
        return $this->handledEvents;
    }
}
