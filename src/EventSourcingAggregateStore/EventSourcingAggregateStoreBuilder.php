<?php

declare(strict_types=1);

namespace Backslash\EventSourcingAggregateStore;

use Backslash\Aggregate\AggregateFactory;
use Backslash\AggregateStore\AggregateStore;
use Backslash\EventBus\EventBusInterface;
use Backslash\EventStore\EventStoreInterface;

class EventSourcingAggregateStoreBuilder
{
    public static function build(
        string $aggregateRootClass,
        EventStoreInterface $eventStore,
        EventBusInterface $eventBus,
    ): AggregateStore {
        return new AggregateStore(
            new EventSourcingAggregateStoreAdapter(
                new AggregateFactory($aggregateRootClass),
                $eventStore,
                $eventBus,
            ),
        );
    }
}
