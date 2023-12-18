<?php

declare(strict_types=1);

namespace Backslash\PdoEventStore;

use Backslash\Aggregate\Metadata;
use Backslash\Aggregate\RecordedEvent;
use Backslash\Aggregate\Stream;
use Backslash\EventStore\EventStore;
use Backslash\Pdo\PdoInterface;
use Backslash\Pdo\PdoProxy;
use Backslash\Serializer\Serializer;
use PDO;
use PHPUnit\Framework\TestCase;

class PdoEventStoreTest extends TestCase
{
    private PdoInterface $pdo;

    public function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PdoProxy(
            function () {
                $pdo = new PDO('sqlite::memory:');
                $pdo->exec(
                    'CREATE TABLE event_store (
                sequence INT,
                aggregate_type VARCHAR,
                aggregate_id VARCHAR,
                aggregate_version INT,
                event_id VARCHAR,
                event_class VARCHAR,
                event_metadata VARCHAR,
                event_payload VARCHAR,
                event_time VARCHAR
            )',
                );
                return $pdo;
            },
        );
    }

    /** @test */
    public function it_stores_and_finds_stream(): void
    {
        $eventIdGenerator = fn () => sha1(uniqid('', true));
        $store = new EventStore(
            new PdoEventStoreAdapter(
                $this->pdo,
                new Config(),
                new Serializer(new JsonEventSerializer()),
                new Serializer(new JsonMetadataSerializer()),
                $eventIdGenerator,
            ),
        );

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
    public function events_are_inspected(): void
    {
        $eventIdGenerator = fn () => sha1(uniqid('', true));
        $store = new EventStore(
            new PdoEventStoreAdapter(
                $this->pdo,
                new Config(),
                new Serializer(new JsonEventSerializer()),
                new Serializer(new JsonMetadataSerializer()),
                $eventIdGenerator,
            ),
        );

        $store->append(
            (new Stream('123', 'type'))
                ->withRecordedEvent(RecordedEvent::createNow(new TestEvent(), new Metadata(), 1))
                ->withRecordedEvent(RecordedEvent::createNow(new TestEvent(), new Metadata(), 2))
                ->withRecordedEvent(RecordedEvent::createNow(new TestEvent(), new Metadata(), 3)),
        );

        $inspector = new TestInspector();
        $store->getAdapter()->inspect($inspector);

        $this->assertCount(3, $inspector->getInspectedEvents());
    }
}
