<?php

declare(strict_types=1);

namespace Backslash\Event;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

class RecordedEventStream implements IteratorAggregate, Countable
{
    private array $recordedEvents;

    public function __construct(RecordedEvent ...$recordedEvents)
    {
        $this->recordedEvents = $recordedEvents;
    }

    public function withRecordedEvents(RecordedEvent ...$recordedEvents): self
    {
        $clone = clone($this);
        $clone->recordedEvents = array_merge($clone->recordedEvents, $recordedEvents);
        return $clone;
    }

    public function getRecordedEvents(): array
    {
        return $this->recordedEvents;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->recordedEvents);
    }

    public function count(): int
    {
        return count($this->recordedEvents);
    }
}
