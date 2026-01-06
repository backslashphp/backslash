<?php

declare(strict_types=1);

namespace Backslash\Scenario;

use Backslash\Clock\Clock;
use Backslash\Event\Metadata;
use Backslash\Event\RecordedEvent;
use Backslash\Event\RecordedEventStream;
use Backslash\EventBus\EventBus;
use Backslash\Shared\Event\StudentRegisteredEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ScenarioEventBusMiddlewareTest extends TestCase
{
    #[Test]
    public function it_publish_a_recorded_event_stream_without_tracing(): void
    {
        $trace = new ScenarioEventBusMiddleware();
        $trace->stopTracing();

        $eventBus = new EventBus();
        $eventBus->addMiddleware($trace);
        $eventBus->publish(new RecordedEventStream(
            RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), new Metadata(), Clock::now()),
        ));

        $this->assertEmpty($trace->getTracedEvents());
    }

    #[Test]
    public function it_publish_a_recorded_event_stream_with_tracing(): void
    {
        $trace = new ScenarioEventBusMiddleware();
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

    #[Test]
    public function it_starts_and_stops_tracing(): void
    {
        $trace = new ScenarioEventBusMiddleware();
        $this->assertFalse($trace->isTracing());

        $trace->startTracing();
        $this->assertTrue($trace->isTracing());

        $trace->stopTracing();
        $this->assertFalse($trace->isTracing());
    }

    #[Test]
    public function it_blocks_publishing_when_blocking_is_enabled(): void
    {
        $trace = new ScenarioEventBusMiddleware();
        $trace->blockPublishing();

        $publishedCount = 0;
        $eventBus = new EventBus();
        $eventBus->addMiddleware($trace);
        $eventBus->subscribe(StudentRegisteredEvent::class, new class ($publishedCount) implements \Backslash\EventBus\EventHandlerInterface {
            public function __construct(private int &$count)
            {
            }
            public function handle(object $event): void
            {
                $this->count++;
            }
        });

        $eventBus->publish(new RecordedEventStream(
            RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), new Metadata(), Clock::now()),
        ));

        $this->assertEquals(0, $publishedCount, 'Event should not be published when blocking is enabled');
    }

    #[Test]
    public function it_publishes_normally_after_unblocking(): void
    {
        $trace = new ScenarioEventBusMiddleware();
        $trace->blockPublishing();
        $trace->unblockPublishing();

        $publishedCount = 0;
        $eventBus = new EventBus();
        $eventBus->addMiddleware($trace);
        $eventBus->subscribe(StudentRegisteredEvent::class, new class ($publishedCount) implements \Backslash\EventBus\EventHandlerInterface {
            public function __construct(private int &$count)
            {
            }
            public function handle(object $event): void
            {
                $this->count++;
            }
        });

        $eventBus->publish(new RecordedEventStream(
            RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), new Metadata(), Clock::now()),
        ));

        $this->assertEquals(1, $publishedCount, 'Event should be published after unblocking');
    }
}
