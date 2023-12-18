<?php

declare(strict_types=1);

namespace Backslash\CommandDispatcher;

final class DispatcherDelegator implements DispatcherInterface
{
    private MiddlewareInterface $middleware;

    private ?DispatcherInterface $next;

    public function __construct(MiddlewareInterface $middleware, ?DispatcherInterface $next = null)
    {
        $this->middleware = $middleware;
        $this->next = $next;
    }

    public function dispatch(object $command): void
    {
        $this->middleware->dispatch($command, $this->next);
    }
}
