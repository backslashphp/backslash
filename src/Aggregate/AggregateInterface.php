<?php

declare(strict_types=1);

namespace Backslash\Aggregate;

/**
 * AggregateInterface represents an aggregate root.
 */
interface AggregateInterface
{
    /**
     * A short string to identify this type of aggregate, i.e. "user".
     */
    public static function getType(): string;

    /**
     * A unique identifier for this type of aggregate.
     */
    public function getAggregateId(): string;

    /**
     * Version corresponds to the total number of events applied to this aggregate.
     */
    public function getVersion(): int;

    /**
     * Retrieves the stream of events applied after the aggregates was rebuilt.
     */
    public function peekNewEvents(): Stream;

    /**
     * Retrieves and deletes the stream of events applied after the aggregates was rebuilt.
     */
    public function pullNewEvents(): Stream;

    /**
     * Applies events in a stream.
     */
    public function replayStream(Stream $stream): void;
}
