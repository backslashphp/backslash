<?php

declare(strict_types=1);

namespace Backslash\StreamEnricher;

use Backslash\Event\RecordedEventStream;
use Backslash\EventBus\EventStreamPublisherInterface;
use Backslash\EventBus\MiddlewareInterface;

final class StreamEnricherEventBusMiddleware implements MiddlewareInterface
{
    private StreamEnricherInterface $enricher;

    public function __construct(StreamEnricherInterface $enricher)
    {
        $this->enricher = $enricher;
    }

    public function publish(RecordedEventStream $stream, EventStreamPublisherInterface $next): void
    {
        $next->publish($this->enricher->enrich($stream));
    }
}
