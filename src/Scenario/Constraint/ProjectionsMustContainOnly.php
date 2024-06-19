<?php

declare(strict_types=1);

namespace Backslash\Scenario\Constraint;

use Backslash\Scenario\UpdatedProjections;
use PHPUnit\Framework\Constraint\Constraint;
use ReflectionClass;

final class ProjectionsMustContainOnly extends Constraint
{
    private string $projectionFqcn;

    public function __construct(string $projectionFqcn)
    {
        if ((new ReflectionClass(Constraint::class))->hasMethod('__construct')) {
            parent::__construct();
        }
        $this->projectionFqcn = $projectionFqcn;
    }

    /**
     * @param UpdatedProjections $updatedProjections
     */
    public function matches($updatedProjections): bool
    {
        $result = true;
        foreach ($updatedProjections->getAll() as $projection) {
            $result = $result && ($projection::class === $this->projectionFqcn);
        }
        return $result;
    }

    public function toString(): string
    {
        return "must contain only instances of {$this->projectionFqcn}";
    }

    protected function failureDescription($other): string
    {
        return 'updated projections ' . $this->toString();
    }
}
