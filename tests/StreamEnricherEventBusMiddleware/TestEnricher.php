<?php

declare(strict_types=1);

namespace Backslash\StreamEnricherEventBusMiddleware;

use Backslash\Aggregate\RecordedEvent;
use Backslash\Aggregate\Stream;
use Backslash\StreamEnricher\StreamEnricherInterface;

class TestEnricher implements StreamEnricherInterface
{
    public function enrich(Stream $stream): Stream
    {
        return array_reduce(
            $stream->getRecordedEvents(),
            function (Stream $enrichedStream, RecordedEvent $envelope) {
                $metadata = $envelope->getMetadata()
                    ->with('foo', 'bar');
                return $enrichedStream->withRecordedEvent($envelope->withMetadata($metadata));
            },
            new Stream($stream->getAggregateId(), $stream->getAggregateType()),
        );
    }
}
