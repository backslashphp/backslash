<?php

declare(strict_types=1);

namespace Backslash\Repository;

use Backslash\Domain\RecordedEventStream;
use Backslash\EventBus\EventStreamPublisherInterface;

class TestEventBusMiddleware implements \Backslash\EventBus\MiddlewareInterface
{
    private bool $called = false;

    public function publish(RecordedEventStream $stream, EventStreamPublisherInterface $next): void
    {
        $this->called = true;
        $next->publish($stream);
    }

    public function wasCalled(): bool
    {
        return $this->called;
    }
}
