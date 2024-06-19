<?php

declare(strict_types=1);

namespace Backslash\Domain;

use InvalidArgumentException;

final class Identifiers
{
    private array $identifiers;

    public function __construct(array $identifiers)
    {
        foreach ($identifiers as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                throw new InvalidArgumentException('Keys and values must be strings.');
            }
        }
        $this->identifiers = $identifiers;
    }

    public function with(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->identifiers[$name] = $value;
        return $clone;
    }

    public function toArray(): array
    {
        return $this->identifiers;
    }
}
