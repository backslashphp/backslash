<?php

declare(strict_types=1);

namespace Backslash\Scenario\Constraint;

use Backslash\Scenario\UpdatedProjections;
use PHPUnit\Framework\Constraint\Constraint;
use ReflectionClass;

final class UpdatedProjectionsMustContain extends Constraint
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
        foreach ($updatedProjections->getAll() as $projection) {
            if ($projection::class === $this->projectionFqcn) {
                return true;
            }
        }
        return false;
    }

    public function toString(): string
    {
        return "must contain instance of {$this->projectionFqcn}";
    }

    protected function failureDescription($other): string
    {
        return 'updated projections ' . $this->toString();
    }
}
