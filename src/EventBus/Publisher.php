<?php

declare(strict_types=1);

namespace Backslash\EventBus;

use Backslash\Aggregate\RecordedEvent;
use Backslash\Aggregate\Stream;

final class Publisher implements EventStreamPublisherInterface
{
    /** @var EventHandlerInterface[][] */
    private array $subscribers = [];

    public function publish(Stream $stream): void
    {
        $recordedEvents = $stream->getRecordedEvents();
        foreach ($recordedEvents as $recordedEvent) {
            $this->notifySubscribers($stream->getAggregateId(), $recordedEvent);
        }
    }

    public function subscribe(string $eventClass, EventHandlerInterface $subscriber): void
    {
        if (!isset($this->subscribers[$eventClass])) {
            $this->subscribers[$eventClass] = [];
        }
        $this->subscribers[$eventClass][] = $subscriber;
    }

    private function notifySubscribers(string $aggregateId, RecordedEvent $recordedEvent): void
    {
        $subscribers = $this->resolveSubscribers($recordedEvent);
        foreach ($subscribers as $subscriber) {
            $subscriber->handle($aggregateId, $recordedEvent);
        }
    }

    /** @return EventHandlerInterface[] */
    private function resolveSubscribers(RecordedEvent $recordedEvent): array
    {
        $name = $recordedEvent->getEvent()::class;
        if (!isset($this->subscribers[$name])) {
            return [];
        }
        $subscribers = [];
        foreach ($this->subscribers[$name] as $subscriber) {
            $subscribers[] = $subscriber;
        }
        return $subscribers;
    }
}
