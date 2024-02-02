<?php

declare(strict_types=1);

namespace Backslash\EventStore;

final class Filter
{
    /** @var string[] */
    private array $eventClasses;

    private array $params = [];

    public function __construct(string ...$eventClasses)
    {
        $this->eventClasses = $eventClasses;
    }

    public function getEventClasses(): array
    {
        return $this->eventClasses;
    }

    public function hasParam(string $key): bool
    {
        return array_key_exists($key, $this->params);
    }

    public function getParam(string $key, mixed $fallbackValue = null): mixed
    {
        return $this->params[$key] ?? $fallbackValue;
    }

    public function withParam(string $key, mixed $value): self
    {
        $clone = clone $this;
        $clone->params[$key] = $value;
        return $clone;
    }

    public function withoutParam(string $key): self
    {
        $clone = clone $this;
        unset($clone->params[$key]);
        return $clone;
    }
}
