<?php

declare(strict_types=1);

namespace Backslash\AggregateStore;

use Backslash\Aggregate\AggregateInterface;

interface AggregateStoreInterface
{
    /** @throws AggregateNotFoundException */
    public function find(string $aggregateId, string $aggregateType): AggregateInterface;

    public function has(string $aggregateId, string $aggregateType): bool;

    public function store(AggregateInterface $aggregate): void;

    public function remove(string $aggregateId, string $aggregateType): void;

    public function purge(): void;
}
