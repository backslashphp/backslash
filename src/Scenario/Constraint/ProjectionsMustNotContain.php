<?php

declare(strict_types=1);

namespace Backslash\Scenario\Constraint;

use Backslash\Scenario\UpdatedProjections;
use PHPUnit\Framework\Constraint\Constraint;
use ReflectionClass;

final class ProjectionsMustNotContain extends Constraint
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
        return "must not contain {$this->projectionClass}";
    }

    /**
     * @param UpdatedProjections $updatedProjections
     */
    public function matches($updatedProjections): bool
    {
        foreach ($updatedProjections->getAll() as $projection) {
            if ($projection::class === $this->projectionClass) {
                return false;
            }
        }

        return true;
    }
}
