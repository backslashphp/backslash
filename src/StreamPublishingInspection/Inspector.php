<?php

declare(strict_types=1);

namespace Backslash\StreamPublishingInspection;

use Backslash\Event\RecordedEvent;
use Backslash\Event\RecordedEventStream;
use Backslash\EventBus\EventBusInterface;
use Backslash\EventStore\InspectorInterface;
use Backslash\EventStore\Query\QueryInterface;

final class Inspector implements InspectorInterface
{
    private EventBusInterface $eventBus;

    private ?QueryInterface $query;

    /** @var ?callable */
    private $before;

    /** @var ?callable */
    private $after;

    public function __construct(EventBusInterface $eventBus, ?QueryInterface $query = null, ?callable $before = null, ?callable $after = null)
    {
        $this->eventBus = $eventBus;
        $this->query = $query;
        $this->before = $before;
        $this->after = $after;
    }

    public function getQuery(): ?QueryInterface
    {
        return $this->query;
    }

    public function inspect(RecordedEvent $recordedEvent): void
    {
        $stream = new RecordedEventStream();
        $stream = $stream->withRecordedEvents($recordedEvent);
        if ($this->before) {
            $before = $this->before;
            $before($recordedEvent);
        }
        $this->eventBus->publish($stream);
        if ($this->after) {
            $after = $this->after;
            $after($recordedEvent);
        }
    }
}
