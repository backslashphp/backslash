<?php

declare(strict_types=1);

namespace Backslash\EventBus;

use Backslash\Domain\RecordedEventStream;

interface MiddlewareInterface
{
    public function publish(RecordedEventStream $stream, EventStreamPublisherInterface $next): void;
}
