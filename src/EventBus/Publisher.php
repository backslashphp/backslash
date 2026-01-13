<?php

declare(strict_types=1);

namespace Backslash\EventBus;

use Backslash\Event\RecordedEvent;
use Backslash\Event\RecordedEventStream;

final class Publisher implements EventStreamPublisherInterface
{
    /** @var EventHandlerInterface[][] */
    private array $subscribers = [];

    /** @var EventHandlerInterface[] */
    private array $globalSubscribers = [];

    public function publish(RecordedEventStream $stream): void
    {
        $recordedEvents = $stream->getRecordedEvents();
        foreach ($recordedEvents as $recordedEvent) {
            $this->forwardToSubscribers($recordedEvent);
        }
    }

    public function subscribe(string $eventClass, EventHandlerInterface $subscriber): void
    {
        if (!isset($this->subscribers[$eventClass])) {
            $this->subscribers[$eventClass] = [];
        }
        $this->subscribers[$eventClass][] = $subscriber;
    }

    public function subscribeAll(EventHandlerInterface $subscriber): void
    {
        $this->globalSubscribers[] = $subscriber;
    }

    private function forwardToSubscribers(RecordedEvent $recordedEvent): void
    {
        $subscribers = $this->resolveSubscribers($recordedEvent);
        foreach ($subscribers as $subscriber) {
            $subscriber->handle($recordedEvent);
        }

        foreach ($this->globalSubscribers as $subscriber) {
            if (!in_array($subscriber, $subscribers, true)) {
                $subscriber->handle($recordedEvent);
            }
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
