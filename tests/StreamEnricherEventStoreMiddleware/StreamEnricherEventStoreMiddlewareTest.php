<?php

declare(strict_types=1);

namespace Backslash\StreamEnricherEventStoreMiddleware;

use Backslash\Clock\Clock;
use Backslash\Event\Metadata;
use Backslash\Event\RecordedEvent;
use Backslash\Event\RecordedEventStream;
use Backslash\EventStore\EventStore;
use Backslash\EventStore\EventStoreInterface;
use Backslash\EventStore\InspectorInterface;
use Backslash\EventStore\MiddlewareInterface;
use Backslash\EventStore\Query\QueryInterface;
use Backslash\EventStore\StoredRecordedEventStream;
use Backslash\Shared\Event\StudentRegisteredEvent;
use Backslash\Shared\PdoEventStore\InMemorySqlitePdoEventStoreFactory;
use Backslash\Shared\StreamEnricher\TestEnricher;
use Backslash\StreamEnricher\StreamEnricherEventStoreMiddleware;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StreamEnricherEventStoreMiddlewareTest extends TestCase
{
    #[Test]
    public function it_enriches_stream(): void
    {
        $mw = new class () implements MiddlewareInterface {
            public ?Metadata $metadata = null;

            public function fetch(?QueryInterface $query, int $fromSequence, EventStoreInterface $next): StoredRecordedEventStream
            {
                return $next->fetch($query, $fromSequence);
            }

            public function append(RecordedEventStream $stream, ?QueryInterface $concurrencyCheck, ?int $expectedSequence, EventStoreInterface $next): void
            {
                $this->metadata = $stream->getRecordedEvents()[0]->getMetadata();
                $next->append($stream, $concurrencyCheck, $expectedSequence);
            }

            public function inspect(InspectorInterface $inspector, EventStoreInterface $next): void
            {
                $next->inspect($inspector);
            }

            public function purge(EventStoreInterface $next): void
            {
                $next->purge();
            }
        };

        $store = new EventStore(InMemorySqlitePdoEventStoreFactory::build());
        $store->addMiddleware($mw);
        $store->addMiddleware(new StreamEnricherEventStoreMiddleware(new TestEnricher()));

        $stream = new RecordedEventStream(
            RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), new Metadata(), Clock::now()),
        );

        $store->append($stream, null, null);

        $this->assertEquals($mw->metadata->toArray(), ['foo' => 'bar']);
    }
}
