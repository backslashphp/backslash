<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Aggregate\Metadata;
use Backslash\Aggregate\RecordedEvent;
use Backslash\Aggregate\Stream;
use PHPUnit\Framework\TestCase;

class InMemoryEventStoreTest extends TestCase
{
    /** @test */
    public function it_stores_and_finds_stream(): void
    {
        $store = new EventStore(new InMemoryEventStoreAdapter());

        $this->assertFalse($store->streamExists('123', 'type'));

        $store->append(
            (new Stream('123', 'type'))
                ->withRecordedEvent(RecordedEvent::createNow(new TestEvent(), new Metadata(), 1)),
        );

        $this->assertTrue($store->streamExists('123', 'type'));

        $store->append(
            (new Stream('123', 'type'))
                ->withRecordedEvent(RecordedEvent::createNow(new TestEvent(), new Metadata(), 2))
                ->withRecordedEvent(RecordedEvent::createNow(new TestEvent(), new Metadata(), 3)),
        );

        $stream = $store->fetch('123', 'type');

        $this->assertEquals('123', $stream->getAggregateId());
        $this->assertEquals('type', $stream->getAggregateType());
        $this->assertCount(3, $stream->getRecordedEvents());

        $version = $store->getVersion('123', 'type');
        $this->assertEquals(3, $version);

        $store->purge();
        $this->assertFalse($store->streamExists('123', 'type'));
    }

    /** @test */
    public function events_are_inspected_in_ascending_chronological_order(): void
    {
        $store = new EventStore(new InMemoryEventStoreAdapter());
        $inspector = new TestInspector();

        $streamId1 = '123';
        $streamId2 = '234';
        $streamId3 = '345';
        $stream1 = $this->createStreamWithOneEvent($streamId1, 1);
        $stream2 = $this->createStreamWithOneEvent($streamId2, 1);
        $stream3 = $this->createStreamWithOneEvent($streamId3, 1);
        $stream4 = $this->createStreamWithOneEvent($streamId1, 2);
        $stream5 = $this->createStreamWithOneEvent($streamId2, 2);
        $stream6 = $this->createStreamWithOneEvent($streamId3, 2);
        $stream7 = $this->createStreamWithOneEvent($streamId1, 3);
        $stream8 = $this->createStreamWithOneEvent($streamId2, 3);
        $stream9 = $this->createStreamWithOneEvent($streamId3, 3);

        $appendedEventStreams = [
            $stream1,
            $stream3,
            $stream6,
            $stream2,
            $stream4,
            $stream7,
            $stream5,
            $stream9,
            $stream8,
        ];
        $store = $this->appendEvents($store, ...$appendedEventStreams);
        $store->getAdapter()->inspect($inspector);

        $expectedInspectedEvents = array_reduce(
            $appendedEventStreams,
            function (array $carry, Stream $item) {
                $carry[] = $item->getRecordedEvents()[0];
                return $carry;
            },
            [],
        );

        $this->assertSame($expectedInspectedEvents, $inspector->getInspectedEvents());
    }

    private function createStreamWithOneEvent(string $streamId, int $version): Stream
    {
        return (new Stream($streamId, 'test'))->withRecordedEvent(
            RecordedEvent::createNow(new TestEvent(), new Metadata(), $version),
        );
    }

    private function appendEvents(EventStore $store, Stream ...$streams): EventStore
    {
        foreach ($streams as $stream) {
            $store->append($stream);
        }
        return $store;
    }
}
