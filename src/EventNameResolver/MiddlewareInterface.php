<?php

declare(strict_types=1);

namespace Backslash\EventNameResolver;

interface MiddlewareInterface
{
    public function resolveClass(string $eventName, EventNameResolverInterface $next): string;

    public function resolveName(string $eventClass, EventNameResolverInterface $next): string;
}
