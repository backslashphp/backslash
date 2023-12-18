<?php

declare(strict_types=1);

namespace Backslash\ProjectionStore;

use Backslash\Projection\ProjectionInterface;

interface ProjectionStoreInterface
{
    /** @throws ProjectionNotFoundException */
    public function find(string $id, string $class): ProjectionInterface;

    public function has(string $id, string $class): bool;

    public function store(ProjectionInterface $projection): void;

    /** @throws ProjectionNotFoundException */
    public function remove(string $id, string $class): void;

    public function purge(): void;

    public function commit(): void;

    public function rollback(): void;
}
