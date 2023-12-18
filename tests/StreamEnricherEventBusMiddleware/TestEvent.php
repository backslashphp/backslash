<?php

declare(strict_types=1);

namespace Backslash\StreamEnricherEventBusMiddleware;

use Backslash\Aggregate\ToArrayTrait;
use Backslash\Aggregate\EventInterface;

class TestEvent implements EventInterface
{
    use ToArrayTrait;
}
