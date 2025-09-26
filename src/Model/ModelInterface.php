<?php

declare(strict_types=1);

namespace Backslash\Model;

use Backslash\Event\RecordedEventStream;

interface ModelInterface
{
    /**
     * Returns new events recorded after the model was loaded.
     */
    public function getChanges(): RecordedEventStream;

    /**
     * Clears new events recorded after the model was loaded.
     */
    public function clearChanges(): void;

    /**
     * Rebuilds the model's current state from an event stream.
     */
    public function applyEvents(RecordedEventStream $stream): void;
}
