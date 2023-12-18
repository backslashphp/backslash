<?php

declare(strict_types=1);

namespace Backslash\StreamEnricher;

use Backslash\Aggregate\Stream;
use Backslash\EventStore\EventStoreInterface;
use Backslash\EventStore\InspectorInterface;
use Backslash\EventStore\MiddlewareInterface;

final class StreamEnricherEventStoreMiddleware implements MiddlewareInterface
{
    private StreamEnricherInterface $enricher;

    public function __construct(StreamEnricherInterface $enricher)
    {
        $this->enricher = $enricher;
    }

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
        $next->append($this->enricher->enrich($stream), $expectedVersion);
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
