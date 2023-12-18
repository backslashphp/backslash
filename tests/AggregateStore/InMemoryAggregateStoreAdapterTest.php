<?php

declare(strict_types=1);

namespace Backslash\AggregateStore;

use PHPUnit\Framework\TestCase;

class InMemoryAggregateStoreAdapterTest extends TestCase
{
    /** @test */
    public function it_finds_aggregate(): void
    {
        $aggregate = new TestAggregate('123');
        $adapter = new InMemoryAggregateStoreAdapter();

        $adapter->store($aggregate);
        $this->assertTrue($adapter->has('123', $aggregate::getType()));
        $found = $adapter->find('123', $aggregate::getType());

        $this->assertTrue($adapter->has('123', $aggregate::getType()));
        $this->assertSame($aggregate, $found);
    }

    /** @test */
    public function it_throws_exception_when_aggregate_not_found(): void
    {
        $this->expectException(AggregateNotFoundException::class);

        $aggregate = new TestAggregate('123');
        $adapter = new InMemoryAggregateStoreAdapter();
        $adapter->store($aggregate);

        $this->assertFalse($adapter->has('123', 'type'));
        $adapter->find('123', 'type');
    }
}
