<?php

declare(strict_types=1);

namespace Backslash\EventSourcingAggregateStore;

use Backslash\Aggregate\AggregateInterface;
use Backslash\Aggregate\AggregateRootTrait;

class TestAggregate implements AggregateInterface
{
    use AggregateRootTrait;

    public static function getType(): string
    {
        return 'test-aggregate';
    }

    public static function create(string $id): self
    {
        $me = new self($id);
        $me->touch();
        return $me;
    }

    public function touch(): void
    {
        $this->apply(new TestEvent());
    }
}
