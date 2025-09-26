<?php

declare(strict_types=1);

namespace Backslash\StreamPublishingInspection;

use Backslash\Clock\Clock;
use Backslash\Event\Metadata;
use Backslash\Event\RecordedEvent;
use Backslash\Event\RecordedEventStream;
use Backslash\EventBus\EventBusInterface;
use Backslash\Shared\Event\StudentRegisteredEvent;
use Backslash\Shared\PdoEventStore\InMemorySqlitePdoEventStoreFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InspectionTest extends TestCase
{
    /** @test */
    public function it_publishes_inspected_events(): void
    {
        /** @var EventBusInterface|MockObject $eventBus */
        $eventBus = $this->createMock(EventBusInterface::class);
        $eventBus->expects($this->exactly(3))->method('publish');

        $adapter = InMemorySqlitePdoEventStoreFactory::build();
        $adapter->append(new RecordedEventStream(
            RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), new Metadata(), Clock::now()),
            RecordedEvent::create(new StudentRegisteredEvent('2', 'Bill'), new Metadata(), Clock::now()),
            RecordedEvent::create(new StudentRegisteredEvent('3', 'Mark'), new Metadata(), Clock::now()),
        ), null, null);

        (new Inspection($adapter, $eventBus))->start();
    }
}
