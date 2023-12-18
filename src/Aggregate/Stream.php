<?php

declare(strict_types=1);

namespace Backslash\Aggregate;

use Countable;

final class Stream implements Countable
{
    private string $aggregateId;

    private string $aggregateType;

    /** @var RecordedEvent[] */
    private array $recordedEvents = [];

    public function __construct(string $aggregateId, string $aggregateType)
    {
        $this->aggregateId = $aggregateId;
        $this->aggregateType = $aggregateType;
    }

    public function count(): int
    {
        return count($this->recordedEvents);
    }

    public function getAggregateId(): string
    {
        return $this->aggregateId;
    }

    public function getAggregateType(): string
    {
        return $this->aggregateType;
    }

    public function withRecordedEvent(RecordedEvent $recordedEvent): self
    {
        $clone = clone $this;
        $clone->recordedEvents[] = $recordedEvent;
        usort(
            $clone->recordedEvents,
            fn (RecordedEvent $a, RecordedEvent $b) => $a->getVersion() <=> $b->getVersion(),
        );
        return $clone;
    }

    /** @return RecordedEvent[] */
    public function getRecordedEvents(): array
    {
        return $this->recordedEvents;
    }
}
