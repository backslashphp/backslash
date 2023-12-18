<?php

declare(strict_types=1);

namespace Backslash\ProjectionStore;

use Backslash\Projection\ProjectionInterface;
use Generator;

final class InMemoryProjectionStoreAdapter implements AdapterInterface
{
    private array $projections;

    public function find(string $id, string $class): ProjectionInterface
    {
        $projection = $this->projections[$class][$id] ?? null;
        if (!$projection) {
            throw ProjectionNotFoundException::forProjection($id, $class);
        }
        return $projection;
    }

    public function findBy(string $class): Generator
    {
        $projections = $this->projections[$class] ?? [];
        foreach ($projections as $projection) {
            yield $projection;
        }
    }

    public function has(string $id, string $class): bool
    {
        return isset($this->projections[$class][$id]);
    }

    public function commit(UnitOfWork $unit): void
    {
        foreach ($unit->getStored() as $projection) {
            $class = $projection::class;
            $id = $projection->getId();
            $this->projections[$class][$id] = $projection;
        }
        foreach ($unit->getRemoved() as $descriptor) {
            unset($this->projections[$descriptor->getClass()][$descriptor->getId()]);
        }
    }

    public function purge(): void
    {
        $this->projections = [];
    }
}
