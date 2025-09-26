<?php

declare(strict_types=1);

namespace Backslash\Event;

use DateTimeImmutable;
use DateTimeInterface;

final class RecordedEvent
{
    private EventInterface $event;

    private Metadata $metadata;

    private DateTimeImmutable $recordTime;

    private function __construct(EventInterface $event, Metadata $metadata, DateTimeImmutable $recordTime)
    {
        $this->event = $event;
        $this->metadata = $metadata;
        $this->recordTime = $recordTime;
    }

    public static function create(EventInterface $event, Metadata $metadata, DateTimeImmutable $recordTime): self
    {
        return new self($event, $metadata, $recordTime);
    }

    public function getEvent(): EventInterface
    {
        return $this->event;
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function getRecordTime(): DateTimeInterface
    {
        return clone $this->recordTime;
    }

    public function withMetadata(Metadata $metadata): self
    {
        $clone = clone $this;
        $clone->metadata = $metadata;
        return $clone;
    }
}
