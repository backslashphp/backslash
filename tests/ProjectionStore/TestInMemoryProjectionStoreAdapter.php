<?php

declare(strict_types=1);

namespace Backslash\ProjectionStore;

use Backslash\Projection\ProjectionInterface;
use Generator;

class TestInMemoryProjectionStoreAdapter implements AdapterInterface
{
    private InMemoryProjectionStoreAdapter $adapter;

    private ?UnitOfWork $unit = null;

    public function __construct()
    {
        $this->adapter = new InMemoryProjectionStoreAdapter();
    }

    public function commit(UnitOfWork $unit): void
    {
        $this->adapter->commit($unit);
        $this->unit = $unit;
    }

    public function getCommitedUnitOfWork(): ?UnitOfWork
    {
        return $this->unit;
    }

    public function find(string $id, string $class): ProjectionInterface
    {
        return $this->adapter->find($id, $class);
    }

    public function findBy(string $class): Generator
    {
        yield $this->adapter->findBy($class);
    }

    public function has(string $id, string $class): bool
    {
        return $this->adapter->has($id, $class);
    }

    public function purge(): void
    {
        $this->adapter->purge();
    }
}
