<?php

declare(strict_types=1);

namespace Backslash\EventSourcingAggregateStore;

use Backslash\Aggregate\AggregateFactory;
use Backslash\AggregateStore\AggregateStore;
use Backslash\EventBus\EventBus;
use Backslash\EventBus\EventHandlerInterface;
use Backslash\EventStore\EventStoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EventSourcedAggregateStoreTest extends TestCase
{
    /** @test */
    public function it_persists_to_event_store_and_dispatches_new_events_on_event_bus(): void
    {
        /** @var EventStoreInterface|MockObject $eventStore */
        $eventStore = $this->createMock(EventStoreInterface::class);
        $eventStore->expects($this->once())->method('append');

        /** @var EventHandlerInterface|MockObject $handler */
        $handler = $this->createMock(EventHandlerInterface::class);
        $handler->expects($this->exactly(3))->method('handle');

        $eventBus = new EventBus();
        $eventBus->subscribe(TestEvent::class, $handler);

        $aggregateStore = new AggregateStore(
            new EventSourcingAggregateStoreAdapter(
                new AggregateFactory(TestAggregate::class),
                $eventStore,
                $eventBus,
            ),
        );

        $aggregate = TestAggregate::create('123');
        $aggregate->touch();
        $aggregate->touch();

        $aggregateStore->store($aggregate);
    }
}
