<?php

declare(strict_types=1);

namespace Backslash\EventBus;

use Backslash\Aggregate\EventInterface;
use Backslash\Aggregate\ToArrayTrait;

class TestEvent1 implements EventInterface
{
    use ToArrayTrait;
}
