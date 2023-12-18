<?php

declare(strict_types=1);

namespace Backslash\AggregateStore;

use Backslash\Aggregate\AggregateInterface;
use Backslash\Aggregate\AggregateRootTrait;

class TestAggregate implements AggregateInterface
{
    use AggregateRootTrait;

    public static function getType(): string
    {
        return 'test-aggregate';
    }
}
