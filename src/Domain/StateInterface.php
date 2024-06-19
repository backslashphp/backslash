<?php

declare(strict_types=1);

namespace Backslash\Domain;

interface StateInterface
{
    /**
     * Retrieves the stream of events applied after the state was rebuilt.
     */
    public function peekNewEvents(): RecordedEventStream;

    /**
     * Retrieves and deletes the stream of events applied after the state was rebuilt.
     */
    public function pullNewEvents(): RecordedEventStream;

    /**
     * Applies events in a stream.
     */
    public function replayEvents(RecordedEventStream $stream): void;
}
