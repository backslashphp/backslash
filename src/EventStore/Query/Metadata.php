<?php

declare(strict_types=1);

namespace Backslash\EventStore\Query;

final class Metadata
{
    private string $name;

    private string $value;

    private function __construct(string $name, string $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public static function is(string $name, string $value): self
    {
        return new self($name, $value);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
