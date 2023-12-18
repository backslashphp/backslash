<?php

declare(strict_types=1);

namespace Backslash\Aggregate;

use DateTimeImmutable;
use DateTimeInterface;

final class RecordedEvent
{
    private EventInterface $event;

    private int $version;

    private Metadata $metadata;

    private DateTimeImmutable $recordTime;

    private function __construct(
        EventInterface $event,
        int $version,
        Metadata $metadata,
        DateTimeImmutable $recordTime,
    ) {
        $this->event = $event;
        $this->version = $version;
        $this->metadata = $metadata;
        $this->recordTime = $recordTime;
    }

    public static function create(
        EventInterface $event,
        int $version,
        Metadata $metadata,
        DateTimeImmutable $recordTime,
    ): self {
        return new self($event, $version, $metadata, $recordTime);
    }

    public static function createNow(EventInterface $event, Metadata $metadata, int $version): self
    {
        return new self($event, $version, $metadata, new DateTimeImmutable());
    }

    public function getEvent(): EventInterface
    {
        return $this->event;
    }

    public function getVersion(): int
    {
        return $this->version;
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
