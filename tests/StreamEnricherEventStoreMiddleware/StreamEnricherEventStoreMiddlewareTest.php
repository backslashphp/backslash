<?php

declare(strict_types=1);

namespace Backslash\StreamEnricherEventStoreMiddleware;

use Backslash\Aggregate\Metadata;
use Backslash\Aggregate\RecordedEvent;
use Backslash\Aggregate\Stream;
use Backslash\EventStore\EventStore;
use Backslash\EventStore\EventStoreInterface;
use Backslash\EventStore\InMemoryEventStoreAdapter;
use Backslash\EventStore\InspectorInterface;
use Backslash\EventStore\MiddlewareInterface;
use Backslash\StreamEnricher\StreamEnricherEventStoreMiddleware;
use PHPUnit\Framework\TestCase;

class StreamEnricherEventStoreMiddlewareTest extends TestCase
{
    /** @test */
    public function it_enriches_stream(): void
    {
        $mw = new class () implements MiddlewareInterface {
            public ?Metadata $metadata = null;

            public function fetch(
                string $aggregateId,
                string $aggregateType,
                int $fromVersion,
                EventStoreInterface $next,
            ): Stream {
                return $next->fetch($aggregateId, $aggregateType, $fromVersion);
            }

            public function streamExists(string $aggregateId, string $aggregateType, EventStoreInterface $next): bool
            {
                return $next->streamExists($aggregateId, $aggregateType);
            }

            public function getVersion(string $aggregateId, string $aggregateType, EventStoreInterface $next): int
            {
                return $next->getVersion($aggregateId, $aggregateType);
            }

            public function append(Stream $stream, ?int $expectedVersion, EventStoreInterface $next): void
            {
                $this->metadata = $stream->getRecordedEvents()[0]->getMetadata();
                $next->append($stream, $expectedVersion);
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

        $store = new EventStore(new InMemoryEventStoreAdapter());
        $store->addMiddleware($mw);
        $store->addMiddleware(new StreamEnricherEventStoreMiddleware(new TestEnricher()));

        $stream = (new Stream('123', 'type'))
            ->withRecordedEvent(RecordedEvent::createNow(new TestEvent(), new Metadata(), 1));

        $store->append($stream);

        $this->assertEquals($mw->metadata->toArray(), ['foo' => 'bar']);
    }
}
