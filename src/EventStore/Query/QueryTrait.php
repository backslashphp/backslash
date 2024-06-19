<?php

declare(strict_types=1);

namespace Backslash\EventStore\Query;

trait QueryTrait
{
    private array $values;

    private bool $negative;

    private array $subqueries = [];

    private function __construct(bool $is, string|int ...$values)
    {
        $this->negative = $is;
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
