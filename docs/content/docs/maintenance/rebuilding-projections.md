---
title: "Rebuilding Projections"
weight: 1
---

Rebuilding projections is a routine task in event-sourced applications. This capability allows you to reconstruct read
models from scratch by replaying the complete event history.

Backslash does not provide a built-in component specifically for rebuilding projections; each application has unique
requirements and should implement rebuilding according to its specific needs. However, Backslash provides essential
tools that facilitate the rebuild process, such as the `Inspector` for replaying events, the `ProjectionStore` for
managing projection lifecycle, and the `EventBus` for routing events to projectors.

## Understanding when to rebuild

Rebuild projections when:

- **Creating new projections**: New read models need historical data from existing events
- **Modifying projection logic**: Changes to how projections interpret events require rebuilding with the updated logic
- **Optimizing projection structure**: Performance improvements or schema changes necessitate reconstruction
- **Fixing projection bugs**: Corrected projector logic must be applied to all historical events
- **Migrating projection storage**: Moving projections to different storage systems requires rebuilding in the new
  location

## Treating projections as disposable

Because events are the source of truth, projections are disposable and easily reconstructed. Events represent what
actually happened in your system; projections are merely interpretations of those events. While projections may
occasionally contain errors due to bugs in projector logic, events always tell the truth.

This fundamental principle means you can confidently delete and rebuild projections whenever needed. The ability to
rebuild projections is what makes CQRS practical for evolving systems.

## Distinguishing projectors from processors

Before rebuilding, understand the critical difference between two types of event handlers:

**Projectors** update projections by reading events and storing read models. They are idempotent and side-effect-free
beyond projection updates. Examples include:

- Building course lists from `CourseDefinedEvent`
- Updating student enrollments from `StudentSubscribedToCourseEvent`
- Maintaining dashboard metrics from various events

**Processors** trigger side effects like sending notifications, calling external APIs, or publishing to message queues.
They should not execute during rebuilds. Examples include:

- Sending confirmation emails when students subscribe to courses
- Notifying external systems of state changes
- Publishing events to message brokers

During rebuilds, register only projectors; never register processors. Processors should execute only once when events
first occur, not during historical replays.

## Understanding EventStore and Inspector

Normal operations use the `Repository` to load models and persist changes. Rebuilding requires direct access to the
`EventStore` and `EventBus` using the `Inspector` component.

The `EventStore` provides an append-only, queryable log of all events. The `Inspector` iterates through stored events
and publishes them to the `EventBus`:

```php
use Backslash\StreamPublishingInspection\Inspector;

$inspector = new Inspector($eventBus);
$eventStore->inspect($inspector);
```

This replays every event in the EventStore, triggering all registered event handlers as if the events were just
published.

## Filtering events during replay

The `Inspector` optionally accepts a `Query` as its second constructor argument to retrieve only a subset of events from
the EventStore. This is particularly useful when rebuilding a single projector; you can pass a query that selects only
events relevant to that projector:

```php
use Backslash\StreamPublishingInspection\Inspector;
use Backslash\EventStore\Query\EventClass;

// Define which events the projector needs
$relevantEvents = [
    CourseDefinedEvent::class,
    CourseCapacityChangedEvent::class,
    StudentSubscribedToCourseEvent::class,
];

// Create a query filtering for these events only
$query = EventClass::in($relevantEvents);

// Inspector will only replay these specific events
$inspector = new Inspector($eventBus, $query);
$eventStore->inspect($inspector);
```

Without a query parameter, the `Inspector` replays all events in the EventStore. When rebuilding all projections, omit
the query; when rebuilding specific projections, use `EventClass::in()` to filter for only the necessary events.

## Tracking rebuild progress

The `Inspector` optionally accepts third and fourth constructor arguments as closures for tracking rebuild progress. The
first closure executes before dispatching each event to the EventBus; the second executes after dispatch:

```php
$eventCount = 0;
$totalEvents = $eventStore->count(); // Hypothetical method

$beforeDispatch = function (RecordedEvent $recordedEvent) use (&$eventCount, $totalEvents) {
    $eventCount++;
    echo sprintf(
        "Processing event %d/%d: %s\n",
        $eventCount,
        $totalEvents,
        $recordedEvent->getEvent()::class
    );
};

$afterDispatch = function (RecordedEvent $recordedEvent) {
    // Log completion, update progress bar, etc.
};

$inspector = new Inspector($eventBus, $query, $beforeDispatch, $afterDispatch);
$eventStore->inspect($inspector);
```

These callbacks are particularly useful for outputting progress to the console, calculating completion percentages, or
logging rebuild metrics.

---

## The serial rebuild process

Rebuilding follows these steps:

1. **Bootstrap the application** with only projectors registered; exclude all processors to prevent side effects
2. **Delete existing projections** by calling `purge()` on the `ProjectionStore`
3. **Disable stream enricher** if your application uses one; metadata enrichment must not occur during replays
4. **Load all events chronologically** from the EventStore and publish them to the EventBus using Inspector
5. **Commit rebuilt projections** by calling `commit()` on the ProjectionStore

The serial approach processes events sequentially, looping through all events one after the other in a single PHP
process. Here's a complete serial rebuild implementation:

```php
<?php

declare(strict_types=1);

use Backslash\CommandDispatcher\DispatcherInterface;
use Backslash\EventBus\EventBusInterface;
use Backslash\EventStore\EventStoreInterface;
use Backslash\ProjectionStore\ProjectionStoreInterface;
use Backslash\StreamPublishingInspection\Inspector;
use Demo\UI\Projection\CourseList\CourseListProjector;
use Demo\UI\Projection\StudentList\StudentListProjector;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $container */
$container = require __DIR__ . '/../bootstrap.php';

/** @var EventStoreInterface $eventStore */
$eventStore = $container->get(EventStoreInterface::class);

/** @var ProjectionStoreInterface $projectionStore */
$projectionStore = $container->get(ProjectionStoreInterface::class);

// Create a dedicated EventBus with only projectors
$eventBus = new EventBus();

// Register projectors (not processors)
$courseListProjector = new CourseListProjector($projectionStore);
$studentListProjector = new StudentListProjector($projectionStore);

$eventBus->subscribe(CourseDefinedEvent::class, $courseListProjector);
$eventBus->subscribe(CourseCapacityChangedEvent::class, $courseListProjector);
$eventBus->subscribe(StudentSubscribedToCourseEvent::class, $courseListProjector);
$eventBus->subscribe(StudentUnsubscribedFromCourseEvent::class, $courseListProjector);
$eventBus->subscribe(StudentRegisteredEvent::class, $courseListProjector);

$eventBus->subscribe(CourseDefinedEvent::class, $studentListProjector);
$eventBus->subscribe(StudentRegisteredEvent::class, $studentListProjector);
$eventBus->subscribe(StudentSubscribedToCourseEvent::class, $studentListProjector);
$eventBus->subscribe(StudentUnsubscribedFromCourseEvent::class, $studentListProjector);

// Step 1: Purge existing projections
$projectionStore->purge();
$projectionStore->commit();

echo "Purged existing projections\n";

// Step 2: Replay all events
$inspector = new Inspector($eventBus);
$eventStore->inspect($inspector);

echo "Replayed all events\n";

// Step 3: Commit rebuilt projections
$projectionStore->commit();

echo "Rebuild complete\n";
```

This serial approach is effective for getting started but processes events sequentially in a single PHP process.

## Disabling stream enrichment

If your application uses a stream enricher, disable it during rebuilds to prevent metadata enrichment. Stream enrichers
typically add contextual information like user IDs, tenant identifiers, or correlation IDs to events as they occur.

During rebuilds, you're replaying historical events that already contain their original metadata. Re-enriching them
would:

- Add incorrect contextual data from the rebuild process rather than the original context
- Potentially modify event metadata in unintended ways
- Cause unnecessary processing overhead

Disable enrichment by not registering the enricher middleware when bootstrapping your rebuild script:

```php
// Normal application bootstrap registers enricher middleware
$eventStore->addMiddleware(new StreamEnricherEventStoreMiddleware($enricher));
$eventBus->addMiddleware(new StreamEnricherEventBusMiddleware($enricher));

// Rebuild script omits enricher middleware entirely
// Just use EventStore and EventBus without enricher middleware
```

Alternatively, implement methods like `enable()` and `disable()` on your stream enricher to toggle activation:

```php
class StreamEnricher implements StreamEnricherInterface
{
    private bool $enabled = true;

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function enrich(EventStreamInterface $stream): EventStreamInterface
    {
        if (!$this->enabled) {
            return $stream;
        }

        // Enrichment logic here
    }
}

// In rebuild script
$enricher->disable();

// After rebuild
$enricher->enable();
```

This approach allows you to keep the enricher middleware registered while controlling when enrichment occurs.

## Implementing parallel rebuilds

For large event stores, parallel rebuilding improves performance by running each projector in a separate PHP process.
This approach reduces memory consumption and CPU usage by distributing work across multiple workers.

One suggested parallel rebuild strategy involves two scripts:

**Main coordinator script** that:

- Deletes all existing projections
- Loops through all projectors
- Launches a background process for each projector, passing the projector class name as an argument
- Waits for all background processes to complete

**Worker script** that:

- Receives the projector class name as an argument
- Determines which events the projector is interested in
- Creates a query filtering for those specific events using `EventClass::in()`
- Uses the `Inspector` with this query to replay only the relevant events
- Creates a dedicated EventBus and registers only that projector
- Instructs the EventStore to inspect using the filtered Inspector
- Commits the rebuilt projections for that projector only

This approach allows each projector to process only its relevant events independently, enabling true parallel execution
where multiple projectors rebuild simultaneously in separate PHP processes.

Each worker performs its own `commit()`, ensuring that projections are persisted independently.

**Important**: This parallel method requires projectors to be fully autonomous. Projectors must not depend on
projections built by other projectors, as those projections may not be available when needed during concurrent
execution. Each projector should derive all necessary information solely from events. When projectors are autonomous,
there is no risk of conflicts between workers since each projector manages distinct projections.

---

## Filtering events for parallel rebuilds

When implementing the worker script, use the `Inspector` with a query to replay only events relevant to the specific
projector. Adding a static method to each projector that returns its subscribed events simplifies worker scripts and
centralizes event subscription knowledge:

```php
class CourseListProjector implements EventHandlerInterface
{
    use EventHandlerTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            CourseDefinedEvent::class,
            CourseCapacityChangedEvent::class,
            StudentSubscribedToCourseEvent::class,
            StudentUnsubscribedFromCourseEvent::class,
        ];
    }

    // Handler methods...
}
```

The worker script can then call this method to determine which events to load:

```php
use Backslash\EventStore\Query\EventClass;
use Backslash\StreamPublishingInspection\Inspector;

// Worker receives projector class name as argument
$projectorClass = $argv[1]; // e.g., CourseListProjector::class

// Get subscribed events from projector
$eventClasses = $projectorClass::getSubscribedEvents();

// Create query to filter for relevant events only
$query = EventClass::in($eventClasses);

// Create and register projector
$eventBus = new EventBus();
$projector = new $projectorClass($projectionStore);

foreach ($eventClasses as $eventClass) {
    $eventBus->subscribe($eventClass, $projector);
}

// Create Inspector with the filtering query
$inspector = new Inspector($eventBus, $query);

// Replay only the filtered events
$eventStore->inspect($inspector);

// Commit the rebuilt projections
$projectionStore->commit();
```

This pattern ensures each worker processes only its necessary events, avoiding unnecessary event handling and improving
rebuild performance. The `Inspector` with the query guarantees that only relevant events are replayed through the
EventBus.

---

## Best practices

**Make the application unavailable during rebuilds.** The application should not be accessible while rebuilding
projections to prevent inconsistent reads and potential data corruption. For live rebuilds without downtime, rebuild
projections in separate storage and ensure new events created during the rebuild are processed after completion; this
requires careful orchestration and is beyond the scope of basic rebuilds.

**Separate projectors from processors.** Maintain clear boundaries between event handlers that update projections and
those that trigger side effects; this separation is essential for safe rebuilds.

**Test rebuild logic before production.** Verify your rebuild implementation works correctly on a copy of production
data before executing against live projections.

**Schedule rebuilds during maintenance windows.** Rebuilding can be resource-intensive for large event stores; schedule
rebuilds during low-traffic periods to minimize impact.

**Monitor rebuild progress.** For large event stores, add logging to track rebuild progress and identify potential
issues early; consider logging after every N events processed.

**Use selective rebuilds when possible.** Rebuild only affected projections rather than all projections to save time and
resources; create temporary EventBus instances with only the necessary projectors.

**Consider parallel rebuilds for scale.** As your event store grows, parallel rebuilds become increasingly valuable for
reducing rebuild time and resource consumption.

**Backup before rebuilding.** Consider backing up projection data before rebuilding in case you need to roll back; this
is especially important for production systems.

**Keep projectors idempotent.** Design projectors to produce the same result regardless of how many times events are
replayed; this ensures reliable rebuilding and recovery.

**Document your rebuild process.** Maintain clear documentation of your rebuild procedures, including when to rebuild,
how to execute rebuilds, and expected duration for different projection sets.
