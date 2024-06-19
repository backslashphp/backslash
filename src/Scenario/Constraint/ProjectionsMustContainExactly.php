<?php

declare(strict_types=1);

namespace Backslash\Scenario\Constraint;

use Backslash\Scenario\UpdatedProjections;
use PHPUnit\Framework\Constraint\Constraint;
use ReflectionClass;

final class ProjectionsMustContainExactly extends Constraint
{
    private int $count;

    private string $projectionFqcn;

    private int $found;

    public function __construct(int $count, string $projectionFqcn)
    {
        if ((new ReflectionClass(Constraint::class))->hasMethod('__construct')) {
            parent::__construct();
        }
        $this->count = $count;
        $this->projectionFqcn = $projectionFqcn;
        $this->found = 0;
    }

    /**
     * @param UpdatedProjections $updatedProjections
     */
    public function matches($updatedProjections): bool
    {
        $this->found = 0;
        foreach ($updatedProjections->getAll() as $projection) {
            if ($projection::class === $this->projectionFqcn) {
                $this->found++;
            }
        }
        return $this->found === $this->count;
    }

    public function toString(): string
    {
        return "must contain exactly {$this->count} instance(s) of {$this->projectionFqcn}, found {$this->found}";
    }

    protected function failureDescription($other): string
    {
        return 'updated projections ' . $this->toString();
    }
}
