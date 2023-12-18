<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Aggregate\Stream;

interface MiddlewareInterface
{
    public function fetch(
        string $aggregateId,
        string $aggregateType,
        int $fromVersion,
        EventStoreInterface $next,
    ): Stream;

    public function streamExists(string $aggregateId, string $aggregateType, EventStoreInterface $next): bool;

    public function getVersion(string $aggregateId, string $aggregateType, EventStoreInterface $next): int;

    public function append(Stream $stream, ?int $expectedVersion, EventStoreInterface $next): void;

    public function inspect(InspectorInterface $inspector, EventStoreInterface $next): void;

    public function purge(EventStoreInterface $next): void;
}
