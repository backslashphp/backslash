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
    public function getAllOf(string $fqcn): array
    {
        return array_filter(
            $this->updatedProjections,
            fn (ProjectionInterface $item) => $item::class === $fqcn,
        );
    }
}
