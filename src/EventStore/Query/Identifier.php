<?php

declare(strict_types=1);

namespace Backslash\EventStore\Query;

final class Identifier
{
    private string $name;

    private string|int $value;

    private function __construct(string $name, string|int $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public static function is(string $name, string|int $value): self
    {
        return new self($name, $value);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string|int
    {
        return $this->value;
    }
}
