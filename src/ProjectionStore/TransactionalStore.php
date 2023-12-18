<?php

declare(strict_types=1);

namespace Backslash\ProjectionStore;

use Backslash\Projection\ProjectionInterface;

final class TransactionalStore implements ProjectionStoreInterface
{
    private AdapterInterface $adapter;

    private UnitOfWork $unit;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $this->unit = new UnitOfWork();
    }

    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    public function find(string $id, string $class): ProjectionInterface
    {
        if ($this->unit->hasStored($id, $class)) {
            return $this->unit->getOneStored($id, $class);
        }
        if ($this->unit->hasRemoved($id, $class)) {
            throw ProjectionNotFoundException::forProjection($id, $class);
        }
        return $this->adapter->find($id, $class);
    }

    public function has(string $id, string $class): bool
    {
        if ($this->unit->hasStored($id, $class)) {
            return true;
        }
        if ($this->unit->hasRemoved($id, $class)) {
            return false;
        }
        return $this->adapter->has($id, $class);
    }

    public function store(ProjectionInterface $projection): void
    {
        $this->unit->store($projection);
    }

    public function remove(string $id, string $class): void
    {
        $this->unit->remove($id, $class);
    }

    public function purge(): void
    {
        $this->adapter->purge();
    }

    public function commit(): void
    {
        $this->adapter->commit($this->unit);
        $this->unit = new UnitOfWork();
    }

    public function rollback(): void
    {
        $this->unit = new UnitOfWork();
    }
}
