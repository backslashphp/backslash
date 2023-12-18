<?php

declare(strict_types=1);

namespace Backslash\Scenario\Constraint;

use Backslash\Scenario\UpdatedProjections;
use PHPUnit\Framework\Constraint\Constraint;
use ReflectionClass;

final class ProjectionsMustContainOnly extends Constraint
{
    private string $projectionClass;

    public function __construct(string $projectionClass)
    {
        if ((new ReflectionClass(Constraint::class))->hasMethod('__construct')) {
            parent::__construct();
        }
        $this->projectionClass = $projectionClass;
    }

    public function toString(): string
    {
        return "must contains only {$this->projectionClass}";
    }

    /**
     * @param UpdatedProjections $updatedProjections
     */
    public function matches($updatedProjections): bool
    {
        $result = true;
        foreach ($updatedProjections->getAll() as $projection) {
            $result = $result && ($projection::class === $this->projectionClass);
        }
        return $result;
    }
}
