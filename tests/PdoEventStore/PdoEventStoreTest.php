<?php

declare(strict_types=1);

namespace Backslash\PdoEventStore;

use Backslash\Clock\Clock;
use Backslash\Event\Metadata;
use Backslash\Event\RecordedEvent;
use Backslash\Event\RecordedEventStream;
use Backslash\EventStore\ConcurrencyException;
use Backslash\EventStore\EventStore;
use Backslash\EventStore\EventStoreInterface;
use Backslash\EventStore\Query\EventClass;
use Backslash\EventStore\Query\EventTime;
use Backslash\EventStore\Query\Identifier;
use Backslash\EventStore\Query\Metadata as MetadataQuery;
use Backslash\EventStore\Query\Sequence;
use Backslash\Shared\Event\StudentNameChangedEvent;
use Backslash\Shared\Event\StudentPreferredColorChangedEvent;
use Backslash\Shared\Event\StudentRegisteredEvent;
use Backslash\Shared\PdoEventStore\InMemorySqlitePdoEventStoreFactory;
use DateTimeImmutable;
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
            ),
            null,
            null,
        );
        $this->store->append(
            new RecordedEventStream(
                RecordedEvent::create(new StudentNameChangedEvent('2', 'Mary', 'Anna'), new Metadata(), Clock::now()),
                RecordedEvent::create(new StudentPreferredColorChangedEvent('1', ['blue', 'green']), new Metadata(), Clock::now()),
            ),
            null,
            null,
        );

        $events = $this->store->fetch($query);
        $this->assertCount(1, $events);

        $query = Identifier::includes('colors', 'red');
        $events = $this->store->fetch($query);
        $this->assertCount(0, $events);

        $query = Identifier::includes('colors', 'blue');
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
    public function it_finds_and_inspects_events(): void
    {
        $this->store->append(
            new RecordedEventStream(
                RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), new Metadata(['foo' => 'a']), new DateTimeImmutable('2024-01-01')),
                RecordedEvent::create(new StudentRegisteredEvent('2', 'Mary'), new Metadata(['foo' => 'b']), new DateTimeImmutable('2024-01-02')),
                RecordedEvent::create(new StudentNameChangedEvent('2', 'Mary', 'Anna'), new Metadata(['bar' => 'c']), new DateTimeImmutable('2024-01-03')),
            ),
            null,
            null,
        );

        $queries = [
            [null, 3],
            [EventClass::is(StudentRegisteredEvent::class), 2],
            [EventClass::is(StudentNameChangedEvent::class), 1],
            [EventClass::in(StudentRegisteredEvent::class, StudentNameChangedEvent::class), 3],
            [EventClass::is('unknown event'), 0],
            [EventClass::isNot(StudentRegisteredEvent::class), 1],
            [Identifier::is('studentId', '1'), 1],
            [Identifier::is('studentId', '2'), 2],
            [Identifier::isNot('studentId', '2'), 1],
            [Identifier::in('studentId', '1', '2'), 3],
            [MetadataQuery::is('foo', 'a'), 1],
            [MetadataQuery::isNot('foo', 'a'), 2],
            [Sequence::upTo(2), 2],
            [Sequence::from(2), 2],
            [Sequence::before(2), 1],
            [Sequence::after(2), 1],
            [Sequence::between(1, 2), 2],
            [EventTime::upTo(new DateTimeImmutable('2024-01-02')), 2],
            [EventTime::from(new DateTimeImmutable('2024-01-03')), 1],
        ];

        foreach ($queries as $query) {
            $inspector = new TestInspector($query[0]);
            $this->store->getAdapter()->inspect($inspector);
            $this->assertCount($query[1], $inspector->getInspectedEvents());
        }
    }
}
