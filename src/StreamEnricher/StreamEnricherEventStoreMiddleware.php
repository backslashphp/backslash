<?php

declare(strict_types=1);

namespace Backslash\StreamEnricher;

use Backslash\Domain\RecordedEventStream;
use Backslash\EventStore\EventStoreInterface;
use Backslash\EventStore\InspectorInterface;
use Backslash\EventStore\MiddlewareInterface;
use Backslash\EventStore\Query\QueryInterface;
use Backslash\EventStore\StoredRecordedEventStream;

final class StreamEnricherEventStoreMiddleware implements MiddlewareInterface
{
    private StreamEnricherInterface $enricher;

    public function __construct(StreamEnricherInterface $enricher)
    {
        $this->enricher = $enricher;
    }

    public function fetch(?QueryInterface $query, int $fromSequence, EventStoreInterface $next): StoredRecordedEventStream
    {
        return $next->fetch($query, $fromSequence);
    }

    public function append(RecordedEventStream $stream, ?QueryInterface $concurrencyCheck, ?int $expectedSequence, EventStoreInterface $next): void
    {
        $next->append($this->enricher->enrich($stream), $concurrencyCheck, $expectedSequence);
    }

    public function inspect(InspectorInterface $inspector, EventStoreInterface $next): void
    {
        $next->inspect($inspector);
    }

    public function purge(EventStoreInterface $next): void
    {
        $next->purge();
    }
}
