<?php

declare(strict_types=1);

namespace Backslash\EventNameResolver;

class MatchingClassEventNameResolverAdapter implements AdapterInterface
{
    public function resolveClass(string $eventName): string
    {
        return $eventName;
    }

    public function resolveName(string $eventClass): string
    {
        return $eventClass;
    }
}
