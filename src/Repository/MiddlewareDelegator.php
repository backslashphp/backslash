<?php

declare(strict_types=1);

namespace Backslash\Repository;

use Backslash\EventStore\Query\QueryInterface;
use Backslash\Model\ModelInterface;

final class MiddlewareDelegator implements RepositoryInterface
{
    private MiddlewareInterface $middleware;

    private ?RepositoryInterface $next;

    public function __construct(MiddlewareInterface $middleware, ?RepositoryInterface $next = null)
    {
        $this->middleware = $middleware;
        $this->next = $next;
    }

    public function loadModel(string $modelClass, ?QueryInterface $query): ModelInterface
    {
        return $this->middleware->loadModel($modelClass, $query, $this->next);
    }

    public function storeChanges(ModelInterface $model): void
    {
        $this->middleware->storeChanges($model, $this->next);
    }
}
