<?php

declare(strict_types=1);

namespace Backslash\Shared\StreamEnricher;

use Backslash\Domain\RecordedEvent;
use Backslash\Domain\RecordedEventStream;
use Backslash\StreamEnricher\StreamEnricherInterface;

class TestEnricher implements StreamEnricherInterface
{
    public function enrich(RecordedEventStream $stream): RecordedEventStream
    {
        return array_reduce(
            $stream->getRecordedEvents(),
            function (RecordedEventStream $enrichedStream, RecordedEvent $recordedEvent) {
                $metadata = $recordedEvent->getMetadata()
                    ->with('foo', 'bar');
                return $enrichedStream->withRecordedEvents($recordedEvent->withMetadata($metadata));
            },
            new RecordedEventStream(),
        );
    }
}
