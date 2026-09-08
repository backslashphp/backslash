<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Event\RecordedEventStream;
use Backslash\EventStore\Query\Query;

interface MiddlewareInterface
{
    public function fetch(Query $query, int $fromSequence, EventStoreInterface $next): StoredRecordedEventStream;

    public function append(RecordedEventStream $stream, Query $concurrencyCheck, ?int $expectedSequence, EventStoreInterface $next): void;

    public function inspect(InspectorInterface $inspector, EventStoreInterface $next): void;

    public function purge(EventStoreInterface $next): void;
}
