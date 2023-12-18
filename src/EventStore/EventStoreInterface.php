<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Aggregate\Stream;

interface EventStoreInterface
{
    /**
     * @throws StreamNotFoundException if no stream was found.
     */
    public function fetch(string $aggregateId, string $aggregateType, int $fromVersion = 0): Stream;

    public function streamExists(string $aggregateId, string $aggregateType): bool;

    /**
     * @throws StreamNotFoundException if no stream was found.
     */
    public function getVersion(string $aggregateId, string $aggregateType): int;

    /**
     * @throws ConcurrencyException
     */
    public function append(Stream $stream, ?int $expectedVersion = null): void;

    public function inspect(InspectorInterface $inspector): void;

    public function purge(): void;
}
