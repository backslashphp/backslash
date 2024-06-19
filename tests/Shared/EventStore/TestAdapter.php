<?php

declare(strict_types=1);

namespace Backslash\Shared\EventStore;

use Backslash\Domain\RecordedEventStream;
use Backslash\EventStore\AdapterInterface;
use Backslash\EventStore\InspectorInterface;
use Backslash\EventStore\Query\QueryInterface;
use Backslash\EventStore\StoredRecordedEventStream;

class TestAdapter implements AdapterInterface
{
    public function fetch(?QueryInterface $query, int $fromSequence = 0): StoredRecordedEventStream
    {
        return new StoredRecordedEventStream();
    }

    public function append(RecordedEventStream $stream, ?QueryInterface $concurrencyCheck, ?int $expectedSequence): void
    {
    }

    public function inspect(InspectorInterface $inspector): void
    {
    }

    public function purge(): void
    {
    }
}
