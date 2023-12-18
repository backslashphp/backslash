<?php

declare(strict_types=1);

namespace Backslash\Scenario;

use Backslash\Projection\ProjectionInterface;
use Countable;

final class UpdatedProjections implements Countable
{
    /** @var ProjectionInterface[] */
    private array $updatedProjections;

    public function __construct(array $updatedProjections)
    {
        $this->updatedProjections = $updatedProjections;
    }

    public function count(): int
    {
        return count($this->updatedProjections);
    }

    /** @return ProjectionInterface[] */
    public function getAll(): array
    {
        return $this->updatedProjections;
    }

    /** @return ProjectionInterface[] */
    public function getAllOf(string $projectionClass): array
    {
        $projections = [];
        foreach ($this->updatedProjections as $projection) {
            if ($projection::class === $projectionClass) {
                $projections[] = $projection;
            }
        }
        return $projections;
    }
}
