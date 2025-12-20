<?php

declare(strict_types=1);

namespace Backslash\Scenario;

use Backslash\CommandDispatcher\Dispatcher;
use Backslash\Event\Metadata;
use Backslash\Event\RecordedEvent;
use Backslash\Event\RecordedEventStream;
use Backslash\EventBus\EventBus;
use Backslash\EventBus\EventHandlerInterface;
use Backslash\EventStore\EventStore;
use Backslash\ProjectionStore\InMemoryProjectionStoreAdapter;
use Backslash\ProjectionStore\ProjectionStore;
use Backslash\Shared\Event\CourseCreatedEvent;
use Backslash\Shared\Event\StudentRegisteredEvent;
use Backslash\Shared\PdoEventStore\InMemorySqlitePdoEventStoreFactory;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class PlayTest extends TestCase
{
    /** @test */
    public function it_accepts_variadic_event_interfaces_in_with_initial_events(): void
    {
        $scenario = new Scenario();

        $handlerCallCount = 0;
        $handler = new class ($handlerCallCount) implements EventHandlerInterface {
            public function __construct(private int &$count) {}

            public function handle(object $event): void
            {
                $this->count++;
            }
        };

        $eventBus = new EventBus();
        $eventBus->subscribe(StudentRegisteredEvent::class, $handler);
        $eventBus->subscribe(CourseCreatedEvent::class, $handler);

        $scenario = new Scenario(
            eventBus: $eventBus,
            dispatcher: new Dispatcher(),
            projectionStore: new ProjectionStore(new InMemoryProjectionStoreAdapter()),
            eventStore: new EventStore(InMemorySqlitePdoEventStoreFactory::build()),
        );

        $scenario->play(
            new Play()
                ->withInitialEvents(
                    new StudentRegisteredEvent('1', 'John'),
                    new CourseCreatedEvent('123', 'MATH101', 'Mathematics'),
                )
        );

        // Events should NOT be published during setup
        $this->assertEquals(0, $handlerCallCount, 'Events should not be published during withInitialEvents setup');
    }

    /** @test */
    public function it_publishes_events_during_dispatch(): void
    {
        $handlerCallCount = 0;
        $handler = new class ($handlerCallCount) implements EventHandlerInterface {
            public function __construct(private int &$count) {}

            public function handle(object $event): void
            {
                $this->count++;
            }
        };

        $eventBus = new EventBus();
        $eventBus->subscribe(StudentRegisteredEvent::class, $handler);

        $eventStore = new EventStore(InMemorySqlitePdoEventStoreFactory::build());

        // Pre-populate event store with an event
        $eventStore->append(
            new RecordedEventStream(
                RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), new Metadata(), new DateTimeImmutable())
            ),
            null,
            null
        );

        $scenario = new Scenario(
            eventBus: $eventBus,
            dispatcher: new Dispatcher(),
            projectionStore: new ProjectionStore(new InMemoryProjectionStoreAdapter()),
            eventStore: $eventStore,
        );

        $scenario->play(
            new Play()
                ->doAction(function () use ($eventBus) {
                    $eventBus->publish(
                        new RecordedEventStream(
                            RecordedEvent::create(new StudentRegisteredEvent('2', 'Jane'), new Metadata(), new DateTimeImmutable())
                        )
                    );
                })
        );

        // Event should be published during doAction
        $this->assertEquals(1, $handlerCallCount, 'Events should be published during doAction');
    }

    /** @test */
    public function it_maintains_backward_compatibility_with_recorded_event_stream(): void
    {
        $scenario = new Scenario(
            eventStore: new EventStore(InMemorySqlitePdoEventStoreFactory::build())
        );

        $scenario->play(
            new Play()
                ->withInitialEvents(
                    new RecordedEventStream(
                        RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), new Metadata(), new DateTimeImmutable()),
                        RecordedEvent::create(new CourseCreatedEvent('123', 'MATH101', 'Math'), new Metadata(), new DateTimeImmutable())
                    )
                )
        );

        // No exception should be thrown
        $this->assertTrue(true);
    }

    /** @test */
    public function it_maintains_backward_compatibility_with_callable(): void
    {
        $scenario = new Scenario(
            eventStore: new EventStore(InMemorySqlitePdoEventStoreFactory::build())
        );

        $scenario->play(
            new Play()
                ->withInitialEvents(
                    fn() => new RecordedEventStream(
                        RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), new Metadata(), new DateTimeImmutable())
                    )
                )
        );

        // No exception should be thrown
        $this->assertTrue(true);
    }
}
