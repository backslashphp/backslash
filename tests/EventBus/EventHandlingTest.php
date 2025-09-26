<?php

declare(strict_types=1);

namespace Backslash\EventBus;

use Backslash\Clock\Clock;
use Backslash\Event\Metadata;
use Backslash\Event\RecordedEvent;
use Backslash\Event\RecordedEventStream;
use Backslash\Shared\Event\StudentNameChangedEvent;
use Backslash\Shared\Event\StudentRegisteredEvent;
use PHPUnit\Framework\TestCase;

class EventHandlingTest extends TestCase
{
    /** @test */
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
}
