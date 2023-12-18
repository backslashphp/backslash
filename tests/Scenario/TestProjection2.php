<?php

declare(strict_types=1);

namespace Backslash\Scenario;

use Backslash\Projection\ProjectionInterface;

class TestProjection2 implements ProjectionInterface
{
    public function getId(): string
    {
        return 'projection2';
    }
}
