<?php

declare(strict_types=1);

namespace Backslash\ProjectionStore;

use Backslash\Projection\ProjectionInterface;

interface MiddlewareInterface
{
    /** @throws ProjectionNotFoundException */
    public function find(string $id, string $class, ProjectionStoreInterface $next): ProjectionInterface;

    public function has(string $id, string $class, ProjectionStoreInterface $next): bool;

    public function store(ProjectionInterface $projection, ProjectionStoreInterface $next): void;

    /** @throws ProjectionNotFoundException */
    public function remove(string $id, string $class, ProjectionStoreInterface $next): void;

    public function purge(ProjectionStoreInterface $next): void;

    public function commit(ProjectionStoreInterface $next): void;

    public function rollback(ProjectionStoreInterface $next): void;
}
