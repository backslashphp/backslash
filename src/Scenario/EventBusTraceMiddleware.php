<?php

declare(strict_types=1);

namespace Backslash\Scenario;

use Backslash\Event\RecordedEventStream;
use Backslash\EventBus\EventStreamPublisherInterface;
use Backslash\EventBus\MiddlewareInterface;

final class EventBusTraceMiddleware implements MiddlewareInterface
{
    private RecordedEventStream $trace;

    private bool $tracing = false;

    private bool $blocking = false;

    public function __construct()
    {
        $this->trace = new RecordedEventStream();
    }

    public function publish(RecordedEventStream $stream, EventStreamPublisherInterface $next): void
    {
        if ($this->blocking) {
            return;
        }

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

    public function blockPublishing(): void
    {
        $this->blocking = true;
    }

    public function unblockPublishing(): void
    {
        $this->blocking = false;
    }
}
