<?php

declare(strict_types=1);

namespace Backslash\StreamPublishingInspection;

use Backslash\Aggregate\RecordedEvent;
use Backslash\Aggregate\Stream;
use Backslash\EventBus\EventBusInterface;
use Backslash\EventStore\Filter;
use Backslash\EventStore\InspectorInterface;

final class Inspector implements InspectorInterface
{
    private EventBusInterface $eventBus;

    /** @var ?callable */
    private $before;

    /** @var ?callable */
    private $after;

    public function __construct(EventBusInterface $eventBus, ?callable $before = null, ?callable $after = null)
    {
        $this->eventBus = $eventBus;
        $this->before = $before;
        $this->after = $after;
    }

    public function getFilter(): Filter
    {
        return new Filter();
    }

    public function inspect(string $aggregateId, string $aggregateType, RecordedEvent $recordedEvent): void
    {
        $stream = new Stream($aggregateId, $aggregateType);
        $stream = $stream->withRecordedEvent($recordedEvent);
        if ($this->before) {
            $before = $this->before;
            $before($aggregateId, $recordedEvent);
        }
        $this->eventBus->publish($stream);
        if ($this->after) {
            $after = $this->after;
            $after($aggregateId, $recordedEvent);
        }
    }
}
