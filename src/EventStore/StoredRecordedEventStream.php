<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Event\RecordedEventStream;

final class StoredRecordedEventStream extends RecordedEventStream
{
    private int $highestSequence = 0;

    public function withHighestSequence(int $highestSequence): self
    {
        $clone = clone $this;
        $clone->highestSequence = $highestSequence;
        return $clone;
    }

    public function getHighestSequence(): int
    {
        return $this->highestSequence;
    }
}
