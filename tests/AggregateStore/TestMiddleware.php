<?php

declare(strict_types=1);

namespace Backslash\AggregateStore;

use Backslash\Aggregate\AggregateInterface;

class TestMiddleware implements MiddlewareInterface
{
    private string $name;

    private array $output;

    public function __construct(string $name, array &$output)
    {
        $this->name = $name;
        $this->output = &$output;
    }

    public function find(string $aggregateId, string $aggregateType, AggregateStoreInterface $next): AggregateInterface
    {
        $this->output[] = 'before find ' . $this->name;
        $aggregate = $next->find($aggregateId, $aggregateType);
        $this->output[] = 'after find ' . $this->name;
        return $aggregate;
    }

    public function has(string $aggregateId, string $aggregateType, AggregateStoreInterface $next): bool
    {
        $this->output[] = 'before exists ' . $this->name;
        $exists = $next->has($aggregateId, $aggregateType);
        $this->output[] = 'after exists ' . $this->name;
        return $exists;
    }

    public function store(AggregateInterface $aggregate, AggregateStoreInterface $next): void
    {
        $this->output[] = 'before store ' . $this->name;
        $next->store($aggregate);
        $this->output[] = 'after store ' . $this->name;
    }

    public function remove(string $aggregateId, string $aggregateType, AggregateStoreInterface $next): void
    {
        $this->output[] = 'before remove ' . $this->name;
        $next->remove($aggregateId, $aggregateType);
        $this->output[] = 'after remove ' . $this->name;
    }

    public function purge(AggregateStoreInterface $next): void
    {
        $this->output[] = 'before purge ' . $this->name;
        $next->purge();
        $this->output[] = 'after purge ' . $this->name;
    }
}
