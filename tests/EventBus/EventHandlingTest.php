<?php

declare(strict_types=1);

namespace Backslash\EventBus;

use Backslash\Clock\Clock;
use Backslash\Event\Metadata;
use Backslash\Event\RecordedEvent;
use Backslash\Event\RecordedEventStream;
use Backslash\Shared\Event\StudentNameChangedEvent;
use Backslash\Shared\Event\StudentRegisteredEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EventHandlingTest extends TestCase
{
    #[Test]
    public function it_publishes_stream_to_subscribed_handlers(): void
    {
        $bus = new EventBus();
        $handler1 = new TestEventHandler1();
        $handler2 = new TestEventHandler2();

        $bus->subscribe(StudentRegisteredEvent::class, $handler1);
        $bus->subscribe(StudentNameChangedEvent::class, $handler2);

        $stream = new RecordedEventStream(
            RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), new Metadata(), Clock::now()),
            RecordedEvent::create(new StudentNameChangedEvent('1', 'John', 'James'), new Metadata(), Clock::now()),
        );

        $this->assertEmpty($handler1->getHandledEvents());
        $this->assertEmpty($handler2->getHandledEvents());

        $bus->publish($stream);

        $this->assertCount(1, $handler1->getHandledEvents());
        $this->assertInstanceOf(StudentRegisteredEvent::class, $handler1->getHandledEvents()[0]);

        $this->assertCount(1, $handler2->getHandledEvents());
        $this->assertInstanceOf(StudentNameChangedEvent::class, $handler2->getHandledEvents()[0]);
    }

    #[Test]
    public function it_publishes_all_events_to_global_handlers(): void
    {
        $bus = new EventBus();
        $globalHandler = new TestGlobalEventHandler();

        $bus->subscribeAll($globalHandler);

        $stream = new RecordedEventStream(
            RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), new Metadata(), Clock::now()),
            RecordedEvent::create(new StudentNameChangedEvent('1', 'John', 'James'), new Metadata(), Clock::now()),
        );

        $this->assertEmpty($globalHandler->getHandledEvents());

        $bus->publish($stream);

        $this->assertCount(2, $globalHandler->getHandledEvents());
        $this->assertInstanceOf(StudentRegisteredEvent::class, $globalHandler->getHandledEvents()[0]);
        $this->assertInstanceOf(StudentNameChangedEvent::class, $globalHandler->getHandledEvents()[1]);
    }

    #[Test]
    public function it_executes_global_handlers_after_specific_handlers(): void
    {
        $bus = new EventBus();
        $specificHandler = new TestEventHandler1();
        $globalHandler = new TestGlobalEventHandler();

        $bus->subscribe(StudentRegisteredEvent::class, $specificHandler);
        $bus->subscribeAll($globalHandler);

        $stream = new RecordedEventStream(
            RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), new Metadata(), Clock::now()),
        );

        $bus->publish($stream);

        // Both handlers should have received the event
        $this->assertCount(1, $specificHandler->getHandledEvents());
        $this->assertCount(1, $globalHandler->getHandledEvents());

        // Verify it's the same event
        $this->assertEquals($specificHandler->getHandledEvents()[0], $globalHandler->getHandledEvents()[0]);
    }

    #[Test]
    public function it_supports_multiple_global_handlers(): void
    {
        $bus = new EventBus();
        $globalHandler1 = new TestGlobalEventHandler();
        $globalHandler2 = new TestGlobalEventHandler();

        $bus->subscribeAll($globalHandler1);
        $bus->subscribeAll($globalHandler2);

        $stream = new RecordedEventStream(
            RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), new Metadata(), Clock::now()),
        );

        $bus->publish($stream);

        $this->assertCount(1, $globalHandler1->getHandledEvents());
        $this->assertCount(1, $globalHandler2->getHandledEvents());
    }

    #[Test]
    public function it_calls_handler_only_once_when_subscribed_both_specifically_and_globally(): void
    {
        $bus = new EventBus();
        $handler = new TestEventHandler1();

        // Subscribe to specific event
        $bus->subscribe(StudentRegisteredEvent::class, $handler);
        // Subscribe to all events
        $bus->subscribeAll($handler);

        $stream = new RecordedEventStream(
            RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), new Metadata(), Clock::now()),
        );

        $bus->publish($stream);

        // Handler should be called only once, not twice
        $this->assertCount(1, $handler->getHandledEvents());
    }
}
