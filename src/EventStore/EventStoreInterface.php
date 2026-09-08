<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Event\RecordedEventStream;
use Backslash\EventStore\Query\Query;

interface EventStoreInterface
{
    public function fetch(Query $query, int $fromSequence = 0): StoredRecordedEventStream;

    /** @throws ConcurrencyException */
    public function append(RecordedEventStream $stream, Query $concurrencyCheck, ?int $expectedSequence): void;

    public function inspect(InspectorInterface $inspector): void;

    public function purge(): void;
}
