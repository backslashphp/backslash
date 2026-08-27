<?php

declare(strict_types=1);

namespace Backslash\ProjectionStoreCommitRepositoryMiddleware;

use Backslash\Projection\ProjectionInterface;
use Backslash\ProjectionStore\AdapterInterface;
use Backslash\ProjectionStore\InMemoryProjectionStoreAdapter;
use Backslash\ProjectionStore\UnitOfWork;
use Generator;

class TestProjectionStore implements AdapterInterface
{
    private array $calls = [];
    private InMemoryProjectionStoreAdapter $adapter;

    public function __construct()
    {
        $this->adapter = new InMemoryProjectionStoreAdapter();
    }

    public function find(string $id, string $class): ProjectionInterface
    {
        $this->calls[] = ['find', $id, $class];
        return $this->adapter->find($id, $class);
    }

    public function findBy(string $class): Generator
    {
        $this->calls[] = ['findBy', $class];
        yield from $this->adapter->findBy($class);
    }

    public function has(string $id, string $class): bool
    {
        $this->calls[] = ['has', $id, $class];
        return $this->adapter->has($id, $class);
    }

    public function commit(UnitOfWork $unit): void
    {
        $this->calls[] = ['commit'];
        $this->adapter->commit($unit);
    }

    public function purge(): void
    {
        $this->calls[] = ['purge'];
        $this->adapter->purge();
    }

    public function getCalls(): array
    {
        return $this->calls;
    }
}
