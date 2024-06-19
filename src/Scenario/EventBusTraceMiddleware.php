<?php

declare(strict_types=1);

namespace Backslash\Scenario;

use Backslash\Domain\RecordedEventStream;
use Backslash\EventBus\EventStreamPublisherInterface;
use Backslash\EventBus\MiddlewareInterface;

final class EventBusTraceMiddleware implements MiddlewareInterface
{
    private RecordedEventStream $trace;

    private bool $tracing = false;

    public function __construct()
    {
        $this->trace = new RecordedEventStream();
    }

    public function publish(RecordedEventStream $stream, EventStreamPublisherInterface $next): void
    {
        $next->publish($stream);
        if ($this->tracing) {
            $this->trace = $this->trace->withRecordedEvents(...$stream->getRecordedEvents());
        }
    }

    public function startTracing(): void
    {
        if ($this->tracing) {
            return;
        }
        $this->tracing = true;
        $this->trace = new RecordedEventStream();
    }

    public function stopTracing(): void
    {
        $this->tracing = false;
    }

    public function clearTrace(): void
    {
        $this->trace = new RecordedEventStream();
    }

    public function isTracing(): bool
    {
        return $this->tracing;
    }

    public function getTracedEvents(): RecordedEventStream
    {
        return $this->trace;
    }
}
