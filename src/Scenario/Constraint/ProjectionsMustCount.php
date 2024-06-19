<?php

declare(strict_types=1);

namespace Backslash\Scenario\Constraint;

use Backslash\Scenario\UpdatedProjections;
use PHPUnit\Framework\Constraint\Constraint;
use ReflectionClass;

final class ProjectionsMustCount extends Constraint
{
    private int $count;

    public function __construct(int $count)
    {
        if ((new ReflectionClass(Constraint::class))->hasMethod('__construct')) {
            parent::__construct();
        }
        $this->count = $count;
    }

    /**
     * @param UpdatedProjections $updatedProjections
     */
    public function matches($updatedProjections): bool
    {
        return count($updatedProjections) === $this->count;
    }

    public function toString(): string
    {
        return "must count {$this->count} projection(s)";
    }

    protected function failureDescription($other): string
    {
        return 'updated projections ' . $this->toString();
    }
}
