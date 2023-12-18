<?php

declare(strict_types=1);

namespace Backslash\Scenario;

use Backslash\Projection\ProjectionInterface;

class TestProjection1 implements ProjectionInterface
{
    public function getId(): string
    {
        return 'projection1';
    }
}
