<?php

declare(strict_types=1);

namespace Backslash\EventStoreReductionInspection;

use Backslash\Aggregate\Metadata;
use Backslash\Aggregate\RecordedEvent;
use Backslash\Aggregate\Stream;
use Backslash\EventStore\EventStore;
use Backslash\EventStore\InMemoryEventStoreAdapter;
use PHPUnit\Framework\TestCase;

class ReductionTest extends TestCase
{
    /** @test */
    public function it_reduces_events(): void
    {
        $eventStore = new EventStore(new InMemoryEventStoreAdapter());
        $eventStore->append(
            (new Stream('123', 'type'))
                ->withRecordedEvent(RecordedEvent::createNow(new TestEvent(), new Metadata(), 1))
                ->withRecordedEvent(RecordedEvent::createNow(new TestEvent(), new Metadata(), 2))
                ->withRecordedEvent(RecordedEvent::createNow(new TestEvent(), new Metadata(), 3)),
        );
        $reduction = new EventStoreReductionInspection($eventStore->getAdapter());

        $this->assertEquals(3, $reduction->inspect(new TestReducer()));
    }
}
