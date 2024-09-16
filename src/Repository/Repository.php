<?php

declare(strict_types=1);

namespace Backslash\Repository;

use Backslash\Domain\StateInterface;
use Backslash\EventBus\EventBusInterface;
use Backslash\EventStore\EventStoreInterface;
use Backslash\EventStore\Query\QueryInterface;

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

    public function load(string $class, ?QueryInterface $query): StateInterface
    {
        return $this->chain->load($class, $query);
    }

    public function store(StateInterface $state): void
    {
        $this->chain->store($state);
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
