<?php

declare(strict_types=1);

namespace Backslash\CacheProjectionStoreMiddleware;

use Backslash\Projection\ProjectionInterface;
use Backslash\ProjectionStore\MiddlewareInterface;
use Backslash\ProjectionStore\ProjectionDescriptor;
use Backslash\ProjectionStore\ProjectionStoreInterface;

final class CacheProjectionStoreMiddleware implements MiddlewareInterface
{
    /** @var ProjectionInterface[] */
    private array $cache = [];

    public function find(string $id, string $class, ProjectionStoreInterface $next): ProjectionInterface
    {
        $key = (new ProjectionDescriptor($id, $class))->getKey();
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }
        $projection = $next->find($id, $class);
        $this->cache[$key] = $projection;
        return $projection;
    }

    public function has(string $id, string $class, ProjectionStoreInterface $next): bool
    {
        $key = (new ProjectionDescriptor($id, $class))->getKey();
        if (array_key_exists($key, $this->cache)) {
            return true;
        }
        return $next->has($id, $class);
    }

    public function store(ProjectionInterface $projection, ProjectionStoreInterface $next): void
    {
        $next->store($projection);
        $key = ProjectionDescriptor::fromProjection($projection)->getKey();
        $this->cache[$key] = $projection;
    }

    public function remove(string $id, string $class, ProjectionStoreInterface $next): void
    {
        $next->remove($id, $class);
        $key = (new ProjectionDescriptor($id, $class))->getKey();
        unset($this->cache[$key]);
    }

    public function purge(ProjectionStoreInterface $next): void
    {
        $next->purge();
        $this->cache = [];
    }

    public function commit(ProjectionStoreInterface $next): void
    {
        $next->commit();
    }

    public function rollback(ProjectionStoreInterface $next): void
    {
        $next->rollback();
        $this->cache = [];
    }
}
