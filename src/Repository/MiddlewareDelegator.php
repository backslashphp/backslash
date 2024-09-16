<?php

declare(strict_types=1);

namespace Backslash\Repository;

use Backslash\Domain\StateInterface;
use Backslash\EventStore\Query\QueryInterface;

final class MiddlewareDelegator implements RepositoryInterface
{
    private MiddlewareInterface $middleware;

    private ?RepositoryInterface $next;

    public function __construct(MiddlewareInterface $middleware, ?RepositoryInterface $next = null)
    {
        $this->middleware = $middleware;
        $this->next = $next;
    }

    public function load(string $class, ?QueryInterface $query): StateInterface
    {
        return $this->middleware->load($class, $query, $this->next);
    }

    public function store(StateInterface $state): void
    {
        $this->middleware->store($state, $this->next);
    }
}
