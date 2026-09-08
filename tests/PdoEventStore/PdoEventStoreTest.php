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
use Backslash\EventStore\Query\Identifier;
use Backslash\EventStore\Query\Metadata as MetadataQuery;
use Backslash\EventStore\Query\Query;
use Backslash\Shared\Event\StudentNameChangedEvent;
use Backslash\Shared\Event\StudentPreferredColorChangedEvent;
use Backslash\Shared\Event\StudentRegisteredEvent;
use Backslash\Shared\PdoEventStore\InMemorySqlitePdoEventStoreFactory;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PdoEventStoreTest extends TestCase
{
    private EventStoreInterface $store;

    public function setUp(): void
    {
        parent::setUp();
        $this->store = new EventStore(InMemorySqlitePdoEventStoreFactory::build());
    }

    #[Test]
    public function it_stores_and_finds_stream(): void
    {
        $query = (new Query())->withItem(EventClass::in(StudentRegisteredEvent::class), Identifier::is('studentId', '1'));
        $events = $this->store->fetch($query);
        $this->assertCount(0, $events);

        $this->store->append(
            new RecordedEventStream(
                RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), new Metadata(), Clock::now()),
                RecordedEvent::create(new StudentRegisteredEvent('2', 'Mary'), new Metadata(), Clock::now()),
            ),
            new Query(),
            null,
        );
        $this->store->append(
            new RecordedEventStream(
                RecordedEvent::create(new StudentNameChangedEvent('2', 'Mary', 'Anna'), new Metadata(), Clock::now()),
                RecordedEvent::create(new StudentPreferredColorChangedEvent('1', ['blue', 'green']), new Metadata(), Clock::now()),
            ),
            new Query(),
            null,
        );

        $events = $this->store->fetch($query);
        $this->assertCount(1, $events);

        $query = (new Query())->withItem(Identifier::is('colors', 'red'));
        $events = $this->store->fetch($query);
        $this->assertCount(0, $events);

        $query = (new Query())->withItem(Identifier::is('colors', 'blue'));
        $events = $this->store->fetch($query);
        $this->assertCount(1, $events);

        $this->store->purge();
        $events = $this->store->fetch(new Query());
        $this->assertCount(0, $events);
    }

    #[Test]
    public function it_prevents_concurrent_writes(): void
    {
        $this->expectException(ConcurrencyException::class);

        $this->store->append(
            new RecordedEventStream(
                RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), new Metadata(), Clock::now()),
            ),
            new Query(),
            null,
        );

        $query = (new Query())->withItem(Identifier::is('studentId', '1'));
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

    #[Test]
    public function it_finds_and_inspects_events(): void
    {
        $this->store->append(
            new RecordedEventStream(
                RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), (new Metadata())->with('foo', 'a'), new DateTimeImmutable('2024-01-01')),
                RecordedEvent::create(new StudentRegisteredEvent('2', 'Mary'), (new Metadata())->with('foo', 'b'), new DateTimeImmutable('2024-01-02')),
                RecordedEvent::create(new StudentNameChangedEvent('2', 'Mary', 'Anna'), (new Metadata())->with('bar', 'c'), new DateTimeImmutable('2024-01-03')),
            ),
            new Query(),
            null,
        );

        $queries = [
            [new Query(), 3],
            [(new Query())->withItem(EventClass::in(StudentRegisteredEvent::class)), 2],
            [(new Query())->withItem(EventClass::in(StudentNameChangedEvent::class)), 1],
            [(new Query())->withItem(EventClass::in(StudentRegisteredEvent::class, StudentNameChangedEvent::class)), 3],
            [(new Query())->withItem(EventClass::in('unknown event')), 0],
            [(new Query())->withItem(Identifier::is('studentId', '1')), 1],
            [(new Query())->withItem(Identifier::is('studentId', '2')), 2],
            [(new Query())->withItem(Identifier::is('studentId', '1'))->withItem(Identifier::is('studentId', '2')), 3],
            [(new Query())->withItem(MetadataQuery::is('foo', 'a')), 1],
        ];

        foreach ($queries as $query) {
            $inspector = new TestInspector($query[0]);
            $this->store->getAdapter()->inspect($inspector);
            $this->assertCount($query[1], $inspector->getInspectedEvents());
        }
    }
}
