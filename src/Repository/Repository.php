<?php

declare(strict_types=1);

namespace Backslash\Repository;

use Backslash\EventBus\EventBusInterface;
use Backslash\EventStore\EventStoreInterface;
use Backslash\EventStore\Query\QueryInterface;
use Backslash\Model\ModelInterface;

final class Repository implements RepositoryInterface
{
    private Core $core;

    /** @var MiddlewareInterface[] */
    private array $middlewares;

    private RepositoryInterface $chain;

    public function __construct(EventStoreInterface $eventStore, EventBusInterface $eventBus)
    {
        $this->core = new Core($eventStore, $eventBus);
        $this->middlewares = [];
        $this->chainMiddlewares();
    }

    public function loadModel(string $modelClass, ?QueryInterface $query): ModelInterface
    {
        return $this->chain->loadModel($modelClass, $query);
    }

    public function storeChanges(ModelInterface $model): void
    {
        $this->chain->storeChanges($model);
    }

    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
        $this->chainMiddlewares();
    }

    /** @return MiddlewareInterface[] */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    private function chainMiddlewares(): void
    {
        $this->chain = array_reduce(
            $this->middlewares,
            fn (RepositoryInterface $carry, MiddlewareInterface $item): RepositoryInterface => new MiddlewareDelegator(
                $item,
                $carry,
            ),
            $this->core,
        );
    }
}
