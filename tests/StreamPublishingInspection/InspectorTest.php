<?php

declare(strict_types=1);

namespace Backslash\StreamPublishingInspection;

use Backslash\Clock\Clock;
use Backslash\Event\Metadata;
use Backslash\Event\RecordedEvent;
use Backslash\Event\RecordedEventStream;
use Backslash\EventBus\EventBusInterface;
use Backslash\EventStore\EventStore;
use Backslash\EventStore\Query\EventClass;
use Backslash\Shared\Event\CourseCreatedEvent;
use Backslash\Shared\Event\StudentRegisteredEvent;
use Backslash\Shared\PdoEventStore\InMemorySqlitePdoEventStoreFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class InspectorTest extends TestCase
{
    #[Test]
    public function it_publishes_all_inspected_events(): void
    {
        /** @var EventBusInterface|MockObject $eventBus */
        $eventBus = $this->createMock(EventBusInterface::class);
        $eventBus->expects($this->exactly(3))->method('publish');

        $eventStore = new EventStore(InMemorySqlitePdoEventStoreFactory::build());
        $eventStore->append(new RecordedEventStream(
            RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), new Metadata(), Clock::now()),
            RecordedEvent::create(new StudentRegisteredEvent('2', 'Bill'), new Metadata(), Clock::now()),
            RecordedEvent::create(new StudentRegisteredEvent('3', 'Mark'), new Metadata(), Clock::now()),
        ), null, null);

        $eventStore->inspect(new Inspector($eventBus, EventClass::notIn()));
    }

    #[Test]
    public function it_publishes_only_selected_inspected_events(): void
    {
        /** @var EventBusInterface|MockObject $eventBus */
        $eventBus = $this->createMock(EventBusInterface::class);
        $eventBus->expects($this->exactly(1))->method('publish');

        $eventStore = new EventStore(InMemorySqlitePdoEventStoreFactory::build());
        $eventStore->append(new RecordedEventStream(
            RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), new Metadata(), Clock::now()),
            RecordedEvent::create(new StudentRegisteredEvent('2', 'Bill'), new Metadata(), Clock::now()),
            RecordedEvent::create(new StudentRegisteredEvent('3', 'Mark'), new Metadata(), Clock::now()),
            RecordedEvent::create(new CourseCreatedEvent('1', 'MATH-101', 'Maths'), new Metadata(), Clock::now()),
        ), null, null);

        $eventStore->inspect(new Inspector($eventBus, EventClass::in(CourseCreatedEvent::class)));
    }
}
