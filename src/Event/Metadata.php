<?php

declare(strict_types=1);

namespace Backslash\Event;

final class Metadata
{
    private array $data = [];

    public function get(string $key): ?string
    {
        if ($this->has($key)) {
            return $this->data[$key];
        }
        return null;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function with(string $key, string $value): self
    {
        $clone = clone $this;
        $clone->data[$key] = $value;
        return $clone;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
