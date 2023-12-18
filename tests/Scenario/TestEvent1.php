<?php

declare(strict_types=1);

namespace Backslash\Scenario;

use Backslash\Aggregate\ToArrayTrait;
use Backslash\Aggregate\EventInterface;

class TestEvent1 implements EventInterface
{
    use ToArrayTrait;
}
