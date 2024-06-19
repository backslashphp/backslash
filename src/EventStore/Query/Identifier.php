<?php

declare(strict_types=1);

namespace Backslash\EventStore\Query;

class Identifier implements QueryInterface
{
    private string $name;

    private array $values;

    private bool $negative;

    private array $subqueries = [];

    private function __construct(string $name, bool $is, string|int ...$values)
    {
        $this->name = $name;
        $this->negative = $is;
        $this->values = $values;
    }

    public static function is(string $name, string|int $value): QueryInterface
    {
        return new self($name, false, $value);
    }

    public static function isNot(string $name, string|int $value): QueryInterface
    {
        return new self($name, true, $value);
    }

    public static function in(string $name, string|int ...$values): QueryInterface
    {
        return new self($name, false, ...$values);
    }

    public static function notIn(string $name, string|int ...$values): QueryInterface
    {
        return new self($name, true, ...$values);
    }

    public function and(QueryInterface $subquery): QueryInterface
    {
        $clone = clone $this;
        $clone->subqueries[] = [LogicOperator::AND, $subquery];
        return $clone;
    }

    public function or(QueryInterface $subquery): QueryInterface
    {
        $clone = clone $this;
        $clone->subqueries[] = [LogicOperator::OR, $subquery];
        return $clone;
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

    public function getSubqueries(): array
    {
        return $this->subqueries;
    }
}
