<?php

declare(strict_types=1);

namespace Backslash\Aggregate;

use PHPUnit\Framework\TestCase;

class AggregateTest extends TestCase
{
    /** @test */
    public function it_creates_new_aggregate(): void
    {
        $aggregate = TestAggregate::create('123', 'foo');

        $this->assertSame('123', $aggregate->getAggregateId());
    }

    /** @test */
    public function it_records_events(): void
    {
        $aggregate = TestAggregate::create('123', 'foo');

        $aggregate->setName('foo');
        $aggregate->setName('bar');
        $aggregate->setName('baz');
        $this->assertCount(3, $aggregate->pullNewEvents());
        $this->assertCount(0, $aggregate->peekNewEvents());
    }

    /** @test */
    public function it_replays_stream(): void
    {
        $agg1 = TestAggregate::create('123', 'john');
        $agg1->setName('Bill');

        $stream = array_reduce(
            $agg1->pullNewEvents()->getRecordedEvents(),
            fn (Stream $stream, RecordedEvent $event) => $stream->withRecordedEvent($event),
            new Stream($agg1->getAggregateId(), $agg1::getType()),
        );

        $agg2 = new TestAggregate('123');
        $agg2->replayStream($stream);

        $this->assertEquals($agg1->getName(), $agg2->getName());
    }
}
