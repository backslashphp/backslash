<?php

declare(strict_types=1);

namespace Backslash\EventBus;

use Backslash\Aggregate\EventInterface;
use Backslash\Aggregate\ToArrayTrait;

class TestEvent2 implements EventInterface
{
    use ToArrayTrait;
}
