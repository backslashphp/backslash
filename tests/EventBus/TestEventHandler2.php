<?php

declare(strict_types=1);

namespace Backslash\EventBus;

use Backslash\Aggregate\RecordedEvent;
use Backslash\Aggregate\EventInterface;

class TestEventHandler2 implements EventHandlerInterface
{
    use EventHandlerTrait;

    /** @var EventInterface[] */
    private array $handledEvents = [];

    public static function getSubscribedEventClasses(): array
    {
        return [
            TestEvent2::class,
        ];
    }

    /** @return EventInterface[] */
    public function getHandledEvents(): array
    {
        return $this->handledEvents;
    }

    private function handleTestEvent2(string $aggregateId, TestEvent2 $event, RecordedEvent $envelope): void
    {
        $this->handledEvents[] = $event;
    }
}
