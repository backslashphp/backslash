<?php

declare(strict_types=1);

namespace Backslash\EventStore\Query;

final class EventClass implements QueryInterface
{
    use SubqueriesTrait;

    private array $values;

    private bool $negative;

    private function __construct(bool $negative, string|int ...$values)
    {
        $this->negative = $negative;
        $this->values = $values;
    }

    public static function is(string|int $value): QueryInterface
    {
        return new self(false, $value);
    }

    public static function isNot(string|int $value): QueryInterface
    {
        return new self(true, $value);
    }

    public static function in(string|int ...$values): QueryInterface
    {
        return new self(false, ...$values);
    }

    public static function notIn(string|int ...$values): QueryInterface
    {
        return new self(true, ...$values);
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function isNegative(): bool
    {
        return $this->negative;
    }
}
