<?php

declare(strict_types=1);

namespace Backslash\ProjectionStore;

use Backslash\Projection\ProjectionInterface;

class TestMiddleware implements MiddlewareInterface
{
    private string $name;

    private array $output;

    public function __construct(string $name, array &$output)
    {
        $this->name = $name;
        $this->output = &$output;
    }

    public function find(string $id, string $class, ProjectionStoreInterface $next): ProjectionInterface
    {
        $this->output[] = 'before find ' . $this->name;
        $projection = $next->find($id, $class);
        $this->output[] = 'after find ' . $this->name;
        return $projection;
    }

    public function has(string $id, string $class, ProjectionStoreInterface $next): bool
    {
        $this->output[] = 'before has ' . $this->name;
        $exists = $next->has($id, $class);
        $this->output[] = 'after has ' . $this->name;
        return $exists;
    }

    public function store(ProjectionInterface $projection, ProjectionStoreInterface $next): void
    {
        $this->output[] = 'before store ' . $this->name;
        $next->store($projection);
        $this->output[] = 'after store ' . $this->name;
    }

    public function remove(string $id, string $class, ProjectionStoreInterface $next): void
    {
        $this->output[] = 'before remove ' . $this->name;
        $next->remove($id, $class);
        $this->output[] = 'after remove ' . $this->name;
    }

    public function purge(ProjectionStoreInterface $next): void
    {
        $this->output[] = 'before purge ' . $this->name;
        $next->purge();
        $this->output[] = 'after purge ' . $this->name;
    }

    public function commit(ProjectionStoreInterface $next): void
    {
        $this->output[] = 'before commit ' . $this->name;
        $next->commit();
        $this->output[] = 'after commit ' . $this->name;
    }

    public function rollback(ProjectionStoreInterface $next): void
    {
        $this->output[] = 'before rollback ' . $this->name;
        $next->rollback();
        $this->output[] = 'after rollback ' . $this->name;
    }
}
