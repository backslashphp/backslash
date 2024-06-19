<?php

declare(strict_types=1);

namespace Backslash\Scenario;

use Backslash\Domain\RecordedEvent;
use Backslash\Domain\RecordedEventStream;
use Countable;

final class PublishedEvents implements Countable
{
    private RecordedEventStream $stream;

    public function __construct(RecordedEventStream $stream)
    {
        $this->stream = $stream;
    }

    public function count(): int
    {
        return count($this->stream);
    }

    /** @return RecordedEvent[] */
    public function getAll(): array
    {
        return $this->stream->getRecordedEvents();
    }

    /** @return RecordedEvent[] */
    public function getAllOf(string $fqcn): array
    {
        return array_filter(
            $this->stream->getRecordedEvents(),
            fn (RecordedEvent $item) => $item->getEvent()::class === $fqcn,
        );
    }
}
