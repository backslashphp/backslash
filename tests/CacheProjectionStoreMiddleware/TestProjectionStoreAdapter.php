<?php

declare(strict_types=1);

namespace Backslash\CacheProjectionStoreMiddleware;

use Backslash\Projection\ProjectionInterface;
use Backslash\ProjectionStore\AdapterInterface;
use Backslash\ProjectionStore\InMemoryProjectionStoreAdapter;
use Backslash\ProjectionStore\UnitOfWork;
use Generator;

class TestProjectionStoreAdapter implements AdapterInterface
{
    private InMemoryProjectionStoreAdapter $adapter;

    private int $hits = 0;

    public function __construct()
    {
        $this->adapter = new InMemoryProjectionStoreAdapter();
    }

    public function reset(): void
    {
        $this->hits = 0;
    }

    public function getHits(): int
    {
        return $this->hits;
    }

    public function find(string $id, string $class): ProjectionInterface
    {
        $this->hits++;
        return $this->adapter->find($id, $class);
    }

    public function findBy(string $class): Generator
    {
        $this->hits++;
        yield $this->adapter->findBy($class);
    }

    public function has(string $id, string $class): bool
    {
        $this->hits++;
        return $this->adapter->has($id, $class);
    }

    public function commit(UnitOfWork $unit): void
    {
        $this->hits++;
        $this->adapter->commit($unit);
    }

    public function purge(): void
    {
        $this->hits++;
        $this->adapter->purge();
    }
}
