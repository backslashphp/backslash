<?php

declare(strict_types=1);

namespace Backslash\EventStoreReductionInspection;

use Backslash\Clock\Clock;
use Backslash\Event\Metadata;
use Backslash\Event\RecordedEvent;
use Backslash\Event\RecordedEventStream;
use Backslash\EventStore\EventStore;
use Backslash\Shared\Event\CourseCreatedEvent;
use Backslash\Shared\Event\StudentRegisteredEvent;
use Backslash\Shared\PdoEventStore\InMemorySqlitePdoEventStoreFactory;
use PHPUnit\Framework\TestCase;

class ReductionTest extends TestCase
{
    /** @test */
    public function it_reduces_events(): void
    {
        $eventStore = new EventStore(InMemorySqlitePdoEventStoreFactory::build());
        $eventStore->append(new RecordedEventStream(
            RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), new Metadata(), Clock::now()),
            RecordedEvent::create(new CourseCreatedEvent('A', 'FR101', 'French 101'), new Metadata(), Clock::now()),
        ), null, null);
        $reduction = new EventStoreReductionInspection($eventStore->getAdapter());

        $this->assertEquals(2, $reduction->inspect(new TestReducer()));
    }
}
