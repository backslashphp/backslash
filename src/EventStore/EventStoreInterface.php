<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Event\RecordedEventStream;
use Backslash\EventStore\Query\QueryInterface;

interface EventStoreInterface
{
    public function fetch(?QueryInterface $query, int $fromSequence = 0): StoredRecordedEventStream;

    /** @throws ConcurrencyException */
    public function append(RecordedEventStream $stream, ?QueryInterface $concurrencyCheck, ?int $expectedSequence): void;

    public function inspect(InspectorInterface $inspector): void;

    public function purge(): void;
}
