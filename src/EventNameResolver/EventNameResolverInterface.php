<?php

declare(strict_types=1);

namespace Backslash\EventNameResolver;

interface EventNameResolverInterface
{
    public function resolveClass(string $eventName): string;

    public function resolveName(string $eventClass): string;
}
