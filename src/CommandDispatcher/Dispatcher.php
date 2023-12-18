<?php

declare(strict_types=1);

namespace Backslash\CommandDispatcher;

final class Dispatcher implements DispatcherInterface
{
    private Router $router;

    /** @var MiddlewareInterface[] */
    private array $middlewares;

    private ?DispatcherInterface $chain = null;

    public function __construct()
    {
        $this->router = new Router();
        $this->middlewares = [];
        $this->chain = null;
    }

    public function dispatch(object $command): void
    {
        if (!$this->chain) {
            $this->chainMiddlewares();
        }
        $this->chain->dispatch($command);
    }

    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
        $this->chain = null;
    }

    /** @return MiddlewareInterface[] */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function registerHandler(string $commandClass, HandlerInterface $handler): void
    {
        $this->router->registerHandler($commandClass, $handler);
    }

    private function chainMiddlewares(): void
    {
        $this->chain = array_reduce(
            $this->middlewares,
            fn (DispatcherInterface $carry, MiddlewareInterface $item): DispatcherInterface => new DispatcherDelegator(
                $item,
                $carry,
            ),
            $this->router,
        );
    }
}
