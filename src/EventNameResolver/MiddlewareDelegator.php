<?php

declare(strict_types=1);

namespace Backslash\EventNameResolver;

final class MiddlewareDelegator implements EventNameResolverInterface
{
    private MiddlewareInterface $middleware;

    private ?EventNameResolverInterface $next;

    public function __construct(MiddlewareInterface $middleware, ?EventNameResolverInterface $next)
    {
        $this->middleware = $middleware;
        $this->next = $next;
    }

    public function resolveClass(string $eventName): string
    {
        return $this->middleware->resolveClass($eventName, $this->next);
    }

    public function resolveName(string $eventClass): string
    {
        return $this->middleware->resolveName($eventClass, $this->next);
    }
}
