<?php

declare(strict_types=1);

namespace Backslash\Scenario;

use Backslash\Aggregate\Stream;
use Backslash\EventBus\EventStreamPublisherInterface;
use Backslash\EventBus\MiddlewareInterface;

final class EventBusTraceMiddleware implements MiddlewareInterface
{
    /** @var Stream[] */
    private array $traceStack = [];

    private bool $tracing = false;

    public function publish(Stream $stream, EventStreamPublisherInterface $next): void
    {
        $next->publish($stream);
        if ($this->tracing) {
            $this->traceStack[] = $stream;
        }
    }

    public function startTracing(): void
    {
        if ($this->tracing) {
            return;
        }
        $this->tracing = true;
        $this->traceStack = [];
    }

    public function stopTracing(): void
    {
        $this->tracing = false;
    }

    public function clearTrace(): void
    {
        $this->traceStack = [];
    }

    public function isTracing(): bool
    {
        return $this->tracing;
    }

    /** @return Stream[] */
    public function getTracedEventStreams(): array
    {
        return $this->traceStack;
    }
}
