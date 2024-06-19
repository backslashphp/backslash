<?php

declare(strict_types=1);

namespace Backslash\Scenario;

use Backslash\Clock\Clock;
use Backslash\Domain\Metadata;
use Backslash\Domain\RecordedEvent;
use Backslash\Domain\RecordedEventStream;
use Backslash\EventBus\EventBus;
use Backslash\Shared\Event\StudentRegisteredEvent;
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
        $eventBus->publish(new RecordedEventStream(
            RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), new Metadata(), Clock::now()),
        ));

        $this->assertEmpty($trace->getTracedEvents());
    }

    /** @test */
    public function it_publish_a_recorded_event_stream_with_tracing(): void
    {
        $trace = new EventBusTraceMiddleware();
        $trace->startTracing();

        $eventBus = new EventBus();
        $eventBus->addMiddleware($trace);
        $eventBus->publish(new RecordedEventStream(
            RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), new Metadata(), Clock::now()),
        ));

        $this->assertCount(1, $trace->getTracedEvents());

        $trace->clearTrace();
        $this->assertEmpty($trace->getTracedEvents());
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
