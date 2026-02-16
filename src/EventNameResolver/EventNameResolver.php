<?php

declare(strict_types=1);

namespace Backslash\EventNameResolver;

class EventNameResolver implements EventNameResolverInterface
{
    private AdapterInterface $adapter;

    /** @var MiddlewareInterface[] */
    private array $middlewares = [];

    private ?EventNameResolverInterface $chain = null;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $this->middlewares = [];
        $this->chainMiddlewares();
    }

    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
        $this->chainMiddlewares();
    }

    public function addInnerMiddleware(MiddlewareInterface $middleware): void
    {
        array_unshift($this->middlewares, $middleware);
        $this->chainMiddlewares();
    }

    /** @return MiddlewareInterface[] */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function resolveClass(string $eventName): string
    {
        return $this->chain->resolveClass($eventName);
    }

    public function resolveName(string $eventClass): string
    {
        return $this->chain->resolveName($eventClass);
    }

    private function chainMiddlewares(): void
    {
        $this->chain = array_reduce(
            $this->middlewares,
            fn (EventNameResolverInterface $carry, MiddlewareInterface $item): EventNameResolverInterface => new MiddlewareDelegator(
                $item,
                $carry,
            ),
            $this->adapter,
        );
    }
}
