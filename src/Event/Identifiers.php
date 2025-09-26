<?php

declare(strict_types=1);

namespace Backslash\Event;

use InvalidArgumentException;

final class Identifiers
{
    private array $identifiers;

    public function __construct(array $identifiers)
    {
        foreach ($identifiers as $key => $value) {
            $this->assertKeyIsString($key);
            $this->assertValueIsStringOrArrayOfStrings($value);
        }
        $this->identifiers = $identifiers;
    }

    public function with(string $name, string|array $value): self
    {
        $this->assertValueIsStringOrArrayOfStrings($value);
        $clone = clone $this;
        $clone->identifiers[$name] = $value;
        return $clone;
    }

    public function toArray(): array
    {
        return $this->identifiers;
    }

    private function assertKeyIsString(mixed $key): void
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException('Key must be a string.');
        }
    }

    private function assertValueIsStringOrArrayOfStrings(mixed $value): void
    {
        if (is_string($value)) {
            return;
        }
        if (
            is_array($value)
            && array_is_list($value)
            && empty(array_filter($value, fn ($item) => !is_string($item)))
        ) {
            return;
        }
        throw new InvalidArgumentException('Value must be a string or an array of strings.');
    }
}
