<?php

declare(strict_types=1);

namespace Backslash\PdoEventStore;

use Backslash\Clock\Clock;
use Backslash\Domain\Metadata;
use Backslash\Domain\RecordedEvent;
use Backslash\Domain\RecordedEventStream;
use Backslash\EventStore\ConcurrencyException;
use Backslash\EventStore\EventStore;
use Backslash\EventStore\EventStoreInterface;
use Backslash\EventStore\Query\EventClass;
use Backslash\EventStore\Query\Identifier;
use Backslash\Shared\Event\StudentNameChangedEvent;
use Backslash\Shared\Event\StudentRegisteredEvent;
use Backslash\Shared\PdoEventStore\InMemorySqlitePdoEventStoreFactory;
use PHPUnit\Framework\TestCase;

class PdoEventStoreTest extends TestCase
{
    private EventStoreInterface $store;

    public function setUp(): void
    {
        parent::setUp();
        $this->store = new EventStore(InMemorySqlitePdoEventStoreFactory::build());
    }

    /** @test */
    public function it_stores_and_finds_stream(): void
    {
        $query = EventClass::in(StudentRegisteredEvent::class)
            ->and(Identifier::is('studentId', '1'));
        $events = $this->store->fetch($query);
        $this->assertCount(0, $events);

        $this->store->append(
            new RecordedEventStream(
                RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), new Metadata(), Clock::now()),
                RecordedEvent::create(new StudentRegisteredEvent('2', 'Mary'), new Metadata(), Clock::now()),
                RecordedEvent::create(new StudentNameChangedEvent('2', 'Mary', 'Anna'), new Metadata(), Clock::now()),
            ),
            null,
            null,
        );

        $events = $this->store->fetch($query);
        $this->assertCount(1, $events);

        $this->store->purge();
        $events = $this->store->fetch(null);
        $this->assertCount(0, $events);
    }

    /** @test */
    public function it_prevents_concurrent_writes(): void
    {
        $this->expectException(ConcurrencyException::class);

        $this->store->append(
            new RecordedEventStream(
                RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), new Metadata(), Clock::now()),
            ),
            null,
            null,
        );

        $query = Identifier::is('studentId', '1');
        $storedEvents = $this->store->fetch($query);

        $this->store->append(
            new RecordedEventStream(
                RecordedEvent::create(new StudentNameChangedEvent('1', 'John', 'Joe'), new Metadata(), Clock::now()),
            ),
            $query,
            $storedEvents->getHighestSequence(),
        );

        $this->store->append(
            new RecordedEventStream(
                RecordedEvent::create(new StudentNameChangedEvent('1', 'John', 'Joe'), new Metadata(), Clock::now()),
            ),
            $query,
            $storedEvents->getHighestSequence(),
        );
    }

    /** @test */
    public function it_inspects_events(): void
    {
        $this->store->append(
            new RecordedEventStream(
                RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), new Metadata(), Clock::now()),
                RecordedEvent::create(new StudentRegisteredEvent('2', 'Mary'), new Metadata(), Clock::now()),
                RecordedEvent::create(new StudentNameChangedEvent('2', 'Mary', 'Anna'), new Metadata(), Clock::now()),
            ),
            null,
            null,
        );

        $inspector = new TestInspector();
        $this->store->getAdapter()->inspect($inspector);

        $this->assertCount(3, $inspector->getInspectedEvents());
    }
}
