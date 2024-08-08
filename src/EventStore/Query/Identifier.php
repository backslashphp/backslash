<?php

declare(strict_types=1);

namespace Backslash\EventStore\Query;

class Identifier implements QueryInterface
{
    use SubqueriesTrait;

    private string $name;

    private array $values;

    private bool $negative;

    private bool $includes;

    private function __construct(string $name, bool $negative, bool $includes, string|int ...$values)
    {
        $this->name = $name;
        $this->negative = $negative;
        $this->includes = $includes;
        $this->values = $values;
    }

    public static function is(string $name, string|int $value): QueryInterface
    {
        return new self($name, false, false, $value);
    }

    public static function isNot(string $name, string|int $value): QueryInterface
    {
        return new self($name, true, false, $value);
    }

    public static function includes(string $name, string|int $value): QueryInterface
    {
        return new self($name, false, true, $value);
    }

    public static function in(string $name, string|int ...$values): QueryInterface
    {
        return new self($name, false, false, ...$values);
    }

    public static function notIn(string $name, string|int ...$values): QueryInterface
    {
        return new self($name, true, false, ...$values);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function isNegative(): bool
    {
        return $this->negative;
    }

    public function isIncludes(): bool
    {
        return $this->includes;
    }
}
