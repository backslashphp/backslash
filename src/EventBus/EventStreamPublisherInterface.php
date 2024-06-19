<?php

declare(strict_types=1);

namespace Backslash\EventBus;

use Backslash\Domain\RecordedEventStream;

interface EventStreamPublisherInterface
{
    public function publish(RecordedEventStream $stream): void;
}
