<?php

declare(strict_types=1);

namespace Backslash\AggregateStore;

use Backslash\Aggregate\AggregateInterface;

final class MiddlewareDelegator implements AggregateStoreInterface
{
    private MiddlewareInterface $middleware;

    private ?AggregateStoreInterface $next;

    public function __construct(MiddlewareInterface $middleware, ?AggregateStoreInterface $next = null)
    {
        $this->middleware = $middleware;
        $this->next = $next;
    }

    public function find(string $aggregateId, string $aggregateType): AggregateInterface
    {
        return $this->middleware->find($aggregateId, $aggregateType, $this->next);
    }

    public function has(string $aggregateId, string $aggregateType): bool
    {
        return $this->middleware->has($aggregateId, $aggregateType, $this->next);
    }

    public function store(AggregateInterface $aggregate): void
    {
        $this->middleware->store($aggregate, $this->next);
    }

    public function remove(string $aggregateId, string $aggregateType): void
    {
        $this->middleware->remove($aggregateId, $aggregateType, $this->next);
    }

    public function purge(): void
    {
        $this->middleware->purge($this->next);
    }
}
