<?php

declare(strict_types=1);

namespace Backslash\ProjectionStore;

use Backslash\Projection\ProjectionInterface;

final class UnitOfWork
{
    /** @var ProjectionInterface[] */
    private array $store = [];

    /** @var ProjectionDescriptor[] */
    private array $remove = [];

    public function store(ProjectionInterface $projection): void
    {
        $descriptor = ProjectionDescriptor::fromProjection($projection);
        $this->store[$descriptor->getKey()] = $projection;
        unset($this->remove[$descriptor->getKey()]);
    }

    public function remove(string $id, string $class): void
    {
        $descriptor = new ProjectionDescriptor($id, $class);
        $this->remove[$descriptor->getKey()] = $descriptor;
        unset($this->store[$descriptor->getKey()]);
    }

    /** @return ProjectionInterface[] */
    public function getStored(): array
    {
        return array_values($this->store);
    }

    public function getOneStored(string $id, string $class): ?ProjectionInterface
    {
        $descriptor = new ProjectionDescriptor($id, $class);
        return $this->store[$descriptor->getKey()] ?? null;
    }

    /** @return ProjectionDescriptor[] */
    public function getRemoved(): array
    {
        return array_values($this->remove);
    }

    public function hasStored(string $id, string $class): bool
    {
        $descriptor = new ProjectionDescriptor($id, $class);
        return isset($this->store[$descriptor->getKey()]);
    }

    public function hasRemoved(string $id, string $class): bool
    {
        $descriptor = new ProjectionDescriptor($id, $class);
        return isset($this->store[$descriptor->getKey()]);
    }
}
