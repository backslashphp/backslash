<?php

declare(strict_types=1);

namespace Backslash\Scenario;

use Backslash\Aggregate\Stream;
use Backslash\EventBus\EventBus;
use PHPUnit\Framework\TestCase;

class EventBusTraceMiddlewareTest extends TestCase
{
    /** @test */
    public function it_publish_a_recorded_event_stream_without_tracing(): void
    {
        $trace = new EventBusTraceMiddleware();
        $trace->stopTracing();

        $eventBus = new EventBus();
        $eventBus->addMiddleware($trace);
        $eventBus->publish(new Stream('id', 'type'));

        $this->assertEmpty($trace->getTracedEventStreams());
    }

    /** @test */
    public function it_publish_a_recorded_event_stream_with_tracing(): void
    {
        $trace = new EventBusTraceMiddleware();
        $trace->startTracing();

        $eventBus = new EventBus();
        $eventBus->addMiddleware($trace);
        $eventBus->publish(new Stream('id', 'type'));

        $this->assertCount(1, $trace->getTracedEventStreams());

        $trace->clearTrace();
        $this->assertEmpty($trace->getTracedEventStreams());
    }

    /** @test */
    public function it_starts_and_stops_tracing(): void
    {
        $trace = new EventBusTraceMiddleware();
        $this->assertFalse($trace->isTracing());

        $trace->startTracing();
        $this->assertTrue($trace->isTracing());

        $trace->stopTracing();
        $this->assertFalse($trace->isTracing());
    }
}
