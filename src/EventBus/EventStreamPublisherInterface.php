<?php

declare(strict_types=1);

namespace Backslash\EventBus;

use Backslash\Event\RecordedEventStream;

interface EventStreamPublisherInterface
{
    public function publish(RecordedEventStream $stream): void;
}
