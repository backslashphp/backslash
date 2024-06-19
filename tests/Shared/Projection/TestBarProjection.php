<?php

declare(strict_types=1);

namespace Backslash\Shared\Projection;

use Backslash\Projection\ProjectionInterface;

class TestBarProjection implements ProjectionInterface
{
    private string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
