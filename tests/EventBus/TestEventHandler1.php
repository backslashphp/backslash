<?php

declare(strict_types=1);

namespace Backslash\EventBus;

use Backslash\Aggregate\RecordedEvent;
use Backslash\Aggregate\EventInterface;

class TestEventHandler1 implements EventHandlerInterface
{
    use EventHandlerTrait;

    /** @var EventInterface[] */
    private array $handledEvents = [];

    public static function getSubscribedEventClasses(): array
    {
        return [
            TestEvent1::class,
        ];
    }

    /** @return EventInterface[] */
    public function getHandledEvents(): array
    {
        return $this->handledEvents;
    }

    private function handleTestEvent1(string $aggregateId, TestEvent1 $event, RecordedEvent $envelope): void
    {
        $this->handledEvents[] = $event;
    }
}
