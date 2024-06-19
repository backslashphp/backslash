<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Domain\RecordedEventStream;
use Backslash\EventStore\Query\QueryInterface;

interface MiddlewareInterface
{
    public function fetch(?QueryInterface $query, int $fromSequence, EventStoreInterface $next): StoredRecordedEventStream;

    public function append(RecordedEventStream $stream, ?QueryInterface $concurrencyCheck, ?int $expectedSequence, EventStoreInterface $next): void;

    public function inspect(InspectorInterface $inspector, EventStoreInterface $next): void;

    public function purge(EventStoreInterface $next): void;
}
