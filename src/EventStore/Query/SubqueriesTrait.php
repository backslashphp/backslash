<?php

declare(strict_types=1);

namespace Backslash\EventStore\Query;

trait SubqueriesTrait
{
    private array $subqueries = [];

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

    public function getSubqueries(): array
    {
        return $this->subqueries;
    }
}
