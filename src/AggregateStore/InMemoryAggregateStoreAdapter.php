<?php

declare(strict_types=1);

namespace Backslash\AggregateStore;

use Backslash\Aggregate\AggregateInterface;

final class InMemoryAggregateStoreAdapter implements AdapterInterface
{
    /** @var AggregateInterface[][] */
    private array $aggregates = [];

    public function find(string $aggregateId, string $aggregateType): AggregateInterface
    {
        if (!$this->has($aggregateId, $aggregateType)) {
            throw AggregateNotFoundException::forAggregate($aggregateId, $aggregateType);
        }
        return $this->aggregates[$aggregateType][$aggregateId];
    }

    public function has(string $aggregateId, string $aggregateType): bool
    {
        return isset($this->aggregates[$aggregateType][$aggregateId]);
    }

    public function store(AggregateInterface $aggregate): void
    {
        $this->aggregates[$aggregate::getType()][$aggregate->getAggregateId()] = $aggregate;
    }

    public function remove(string $aggregateId, string $aggregateType): void
    {
        unset($this->aggregates[$aggregateType][$aggregateId]);
    }

    public function purge(): void
    {
        $this->aggregates = [];
    }
}
