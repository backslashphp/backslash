# Introduction

Backslash is a modern PHP library for building event-sourced applications.

This guide walks you through the core concepts, usage patterns, and best practices for developing a Backslash-based
system.

## Prerequisites

This documentation assumes you're already familiar with the fundamentals of event sourcing and CQRS. If you're
new to these concepts, here are some helpful resources to get started:

- [Martin Fowler on Event Sourcing](https://martinfowler.com/eaaDev/EventSourcing.html): A clear and concise
  introduction from one of the most trusted voices in software architecture.
- [Event Store – What Is Event Sourcing?](https://eventstore.com/docs/event-sourcing-basics/): A practical overview from
  the maintainers of EventStoreDB.
- [Greg Young's Original CQRS Introduction (2007)](https://web.archive.org/web/20230324113014/https://cqrs.files.wordpress.com/2010/11/cqrs_documents.pdf):
  The classic paper that introduced CQRS — dense but foundational.
- [Microsoft Docs: CQRS Pattern](https://learn.microsoft.com/en-us/azure/architecture/patterns/cqrs): A pragmatic
  breakdown of the CQRS pattern with diagrams and trade-offs.

## Key features

- **Framework agnostic** — integrates seamlessly with any PHP framework or runs standalone.
- **Compliance with Dynamic Consistency Boundary** — fully adheres to
  the [specification](https://dcb.events/specification/), enabling strong consistency boundaries without relying on
  aggregates.
- **Built-in persistence adapters** — includes PDO-compatible adapters for event and projection storage.
- **Middleware support** — core components are extensible via middleware pipelines for easy customization.

# Installation

## Requirements

## Composer

## Database setup

## Quick start

The fastest way to understand Backslash is to explore the [demo application](https://github.com/backslashphp/demo):

```sh
git clone https://github.com/backslashphp/demo.git
cd demo
composer install
cd bin
php demo.php
```

# Architecture overview

Backslash is designed around the core principles of event sourcing and CQRS. Its modular, lightweight, and
framework-agnostic architecture allows for gradual adoption or integration into existing systems.

**Core components** are provided by Backslash and handle the infrastructure behind event sourcing and CQRS.

**Application components** are written by the developer to implement use cases and domain behavior.

| Component         | Description                                                                                | Core | Application |
|-------------------|--------------------------------------------------------------------------------------------|:----:|:-----------:|
| Events            | Represent facts that have occurred within the system.                                      |      |      ✓      |
| States            | Encapsulate domain rules and emit events that reflect changes.                             |      |      ✓      |
| Queries           | Define how to retrieve past events relevant to a particular decision or use case.          |      |      ✓      |
| `Repository`      | Orchestrates state persistence and event dispatch through the `EventStore` and `EventBus`. |  ✓   |             |
| Commands          | Express a user intention or request to perform an operation.                               |      |      ✓      |
| Command handlers  | Contain the logic to process commands and update the system accordingly.                   |      |      ✓      |
| `Dispatcher`      | Routes commands to their respective handlers.                                              |  ✓   |             |
| `EventStore`      | Stores events as an append-only, queryable log.                                            |  ✓   |             |
| `EventBus`        | Delivers published events to registered handlers.                                          |  ✓   |             |
| Event handlers    | Respond to events to trigger side effects or update projections.                           |      |      ✓      |
| Projections       | Materialized views of application state, updated by handling past events.                  |      |      ✓      |
| `ProjectionStore` | Persists and retrieves projections for efficient querying.                                 |  ✓   |             |
| `Scenario`        | Provides helpers for writing BDD-style tests using PHPUnit.                                |  ✓   |             |
| Testing scenarios | Define given/when/then test flows using the `Scenario` test helpers.                       |      |      ✓      |
| `PdoProxy`        | Delays the creation of a PDO connection until it’s needed.                                 |  ✓   |             |
| `Serializer`      | Handles the serialization and deserialization of events and projections.                   |  ✓   |             |
| Middlewares       | Add custom logic around core components operations.                                        |  ✓   |      ✓      |
| Stream enrichers  | Modify or enhance event data during event processing.                                      |      |      ✓      |

# Modeling events

In Backslash, events are immutable objects that capture something meaningful that happened in your domain.

Here's an example of an event class:

```php
use Backslash\Event\EventInterface;
use Backslash\Event\Identifiers;
use Backslash\Event\ToArrayTrait;

readonly class StudentSubscribedToCourseEvent implements EventInterface
{
    use ToArrayTrait;

    public function __construct(
        public string $studentId,
        public string $courseId,
    ) {
    }

    public function getIdentifiers(): Identifiers
    {
        return new Identifiers([
            'studentId' => $this->studentId,
            'courseId' => $this->courseId,
        ]);
    }
}
```

## The `EventInterface`

Event classes must implement the `Backslash\Domain\EventInterface`:

```php
interface EventInterface
{
    public function getIdentifiers(): Identifiers;

    public function toArray(): array;

    public static function fromArray(array $data): self;
}
```

- `getIdentifiers()` returns one or more identifiers used to scope the event within a consistency boundary.
- `toArray()` serializes the event data into an array format suitable for storage.
- `fromArray()` rehydrates the event from its serialized form.

## Identifiers for consistency boundary

Unlike traditional event sourcing that usually associates events with a single aggregate root, this event returns
identifiers for multiple distinct entities — the student and the course. This approach is typical of the Dynamic
Consistency Boundary specification, which allows consistency boundaries to span multiple related entities without
forcing them into a single aggregate.

## Event serialization

Backslash provides a `Backslash\Domain\ToArrayTrait` that uses reflection on class attributes to automatically implement
`toArray()` and `fromArray()`. To use it correctly:

- All attributes must be **scalar types** (`string`, `int`, `float`, `bool`, or `null`), or arrays of scalar types
- The **constructor parameters must match the attributes exactly** — by **name** and **type**
- You may use **promoted properties** or define properties and constructor separately — either works, as long as they
  match

> ⚠️ If your event includes value objects or more complex structures, you'll need to implement `toArray()` and
`fromArray()` manually.

# Making decisions from events

In traditional event-sourced systems, **aggregates** define a static consistency boundary and are responsible for
enforcing domain invariants. They are rebuilt from a stream of events that all relate to a single aggregate root.

Backslash takes a different approach by embracing the flexibility of the **Dynamic Consistency Boundary** through the
use of **Queries** and **States**.

**States** are the core decision-making components in your application. They are rebuilt from a targeted subset of
events selected by a **Query**. Queries match events based on their **type** and **identifiers**, which means the
resulting event stream can span **multiple entities**, not just one.

To make a business decision, you call a method on a **State** object. The State evaluates the current situation and
emits **new events** in response.

Backslash's **Repository** handles all orchestration behind the scenes — including event loading, state hydration,
conditional appending, and event publishing.

In recap, your responsibilities as a developer are limited to five steps:

1. Model a **State** that encapsulates part of your **domain logic**.
2. Build a **Query** to select the relevant events.
3. Retrieve the **State** from the **Repository** using that query.
4. Call a **decision method** on the State (e.g., `subscribe()`).
5. Store the State back to the **Repository**.

Let's walk through each step in more detail.

## Step 1: Modeling the state

A **State** is a class that encapsulates part of your domain logic. It is responsible for making decisions based on past
events and emitting new ones when appropriate.

A State class must implement the `StateInterface`:

```php
interface StateInterface
{
    public function peekNewEvents(): RecordedEventStream;

    public function pullNewEvents(): RecordedEventStream;

    public function replayEvents(RecordedEventStream $stream): void;
}
```

The easiest way to implement this interface is by extending `AbstractState`, which takes care of replaying past events
and tracking new ones.

### Example

Here's an example of a State that manages course capacity:

```php
use Backslash\Event\AbstractState;

class CourseCapacityState extends AbstractState
{
    private int $capacity = 0;
    private bool $courseDefined = false;

    public function change(string $courseId, int $newCapacity): void
    {
        /* Course must be defined */
        if (!$this->courseDefined) {
            throw new CourseNotDefinedException();
        }
        
        /* New capacity must be greater than 0 */
        if ($newCapacity <= 0) {
            throw new CourseCapacityInvalidException();
        }
        
        /* New capacity must be different than the current one (idempotency) */
        if ($newCapacity !== $this->capacity) {
            return;
        }
        
        $this->apply(new CourseCapacityChangedEvent($courseId, $newCapacity));
    }

    protected function applyCourseDefinedEvent(CourseDefinedEvent $event): void
    {
        $this->capacity = $event->capacity;
        $this->courseDefined = true;
    }

    protected function applyCourseCapacityChangedEvent(CourseCapacityChangedEvent $event): void
    {
        $this->capacity = $event->capacity;
    }
}
```

### Apply methods

Each event that influences the state should have a corresponding `apply*()` method. For example, to handle a
`CourseCapacityChangedEvent`, the method must be named
`applyCourseCapacityChangedEvent()`:

  ```php
  protected function applyCourseCapacityChangedEvent(CourseCapacityChangedEvent $event): void
  {
      $this->capacity = $event->new;
  }
  ```

These methods are automatically invoked by `AbstractState` during the rebuild process. Their role is to update the
internal private attributes of the object based on the event's data.

### Decision methods

Public methods like `change()` are used to enforce business rules. They inspect internal attributes, determine whether a
change is valid, and if so, **apply new events** using `$this->apply(...)`. If a rule is violated, they may throw
exceptions or simply return without doing anything.

Decision methods must be deterministic and free of side effects.

### Rebuilding from past events

When a State is loaded from the Repository, it is first instantiated with no prior data. Then, Backslash replays a
stream of historical events. For each event, the matching `apply*()` method is called, gradually bringing the State to
its current representation.

This replay mechanism ensures that the State is always fully derived from the event history.

### No return values

Decision methods should never return anything. Their sole responsibility is to express intent by applying new events.
All consequences of a decision — including side effects — should be captured through events and handled elsewhere in the
system.

## Step 2: Defining the query

A **Query** selects the events that will be replayed to rebuild a State. This is how you define a **dynamic consistency
boundary** — choosing exactly which events are relevant for a given decision.

Backslash provides a set of filter classes, each focusing on a different aspect of an event. These filters can be
combined using fluent `and()` and `or()` methods to form complex logical expressions, much like a `WHERE` clause in SQL.

| Class        | Filters by...                                 | Methods                                                |
|--------------|-----------------------------------------------|--------------------------------------------------------|
| `EventClass` | The event's fully qualified class name (FQCN) | `is()`, `in()`, `isNot()`, `notIn()`                   |
| `Identifier` | Event identifiers                             | `is()`, `in()`, `includes()`, `isNot()`, `notIn()`     |
| `EventTime`  | The time the event was recorded               | `upTo()`, `from()`                                     |
| `Metadata`   | Metadata fields (see Event Enrichment)        | `is()`, `in()`, `isNot()`, `notIn()`                   |
| `Sequence`   | The event’s sequence number                   | `upTo()`, `from()`, `before()`, `after()`, `between()` |

In most cases, you’ll only need `EventClass` and `Identifier`.

Here's an example:

```php
$query = EventClass::in(
            CourseCapacityChangedEvent::class,
            CourseDefinedEvent::class,
        )
        ->and(
            Identifier::is('courseId', '123')
        );
```

This query matches:

- Events of class `CourseCapacityChangedEvent` or `CourseDefinedEvent`,
- And that have the identifier `courseId` with the value `"123"`.

You can combine filters as deeply as needed to match your domain logic. Queries are both expressive and lightweight,
allowing you to model flexible boundaries without imposing structure on your events.

## Step 3: Fetching the state

To retrieve a **State** instance, pass the **Query** and the fully qualified class name (FQCN) of the desired State to
the **Repository**.

```php
$query = EventClass::in(
            CourseCapacityChangedEvent::class,
            CourseDefinedEvent::class,
        )
        ->and(
            Identifier::is('courseId', $courseId)
        );

/** @var CourseCapacityState $state */
$state = $this->getRepository()->load(CourseCapacityState::class, $query);
```

The **Repository** will instantiate a fresh `CourseCapacityState`, replay the events matching the query, and return a
fully rebuilt state, ready to make a decision.

## Step 4: Calling the decision method

Once you’ve rebuilt the State, you can invoke one of its decision methods — these are public methods that encapsulate
domain rules and apply new events when the rules are satisfied.

These methods inspect the current state, enforce domain invariants, and use $this->apply(...) to record new events. If a
rule is violated, they may throw a domain exception or silently do nothing.

```php
$state->change($courseId, $newCapacity);
```

In this example, `change()` is a decision method on the `CourseCapacityState`. It checks whether the course is defined
and whether the new capacity is valid. If so, it applies a new `CourseCapacityChangedEvent`.

Decision methods:

- **Must not return anything** — their purpose is to express intent via events.
- **Should be side-effect free** — they modify only the internal state by applying events.
- **May throw exceptions** if invariants are violated or inputs are invalid.

After calling a decision method, the state holds new events internally. These are not yet persisted — that happens in
the next step.

## Step 5: Storing the state

After a decision method has been called, the **State** holds one or more new events internally. To persist these events
and publish them to the rest of your application, you must store the State back to the `Repository`.

```php
$this->getRepository()->store($state);
```

Storing the state triggers a series of actions handled behind the scenes:

1. **New events are extracted** from the State.
2. An **append condition** ensures that no conflicting events have been written since the state was originally loaded.
3. If the append is successful:
    - Events are **persisted** to the `EventStore`.
    - Events are **dispatched** to the `EventBus` and delivered to their handlers.

If the append condition fails, a `ConcurrencyException` is thrown — this typically means another process stored new
events that match your query after you loaded the state. In that case, you may choose to retry, log the conflict, or
take a custom recovery path depending on your application logic.

## Full example

```php
use Backslash\EventStore\ConcurrencyException;
use Backslash\EventStore\Query\EventClass;
use Backslash\EventStore\Query\Identifier;

/* Define the query */
$query = EventClass::in(
            CourseCapacityChangedEvent::class,
            CourseDefinedEvent::class,
        )
        ->and(
            Identifier::is('courseId', $courseId)
        );

/* Load the state */
$state = $this->getRepository()->load(CourseCapacityState::class, $query);

/* Call a decision method */
$state->change($courseId, $newCapacity);

/* Store the state and persist new events */
try {
   $this->getRepository()->store($state);
} catch (ConcurrencyException) {
    /* Another process stored conflicting events — handle retry or abort */
}
```

## Best practices with states

When designing and implementing State classes, keep the following practices in mind to ensure clarity, correctness, and
maintainability:

### Assert entity existence

Before applying a decision, make sure the required entities or preconditions exist. For example, don’t allow a course
capacity change if the course hasn’t been defined:

```php
if (!$this->courseDefined) {
  throw new CourseNotDefinedException();
}
```

This helps catch errors early and keeps the event stream meaningful.

### Limit the scope

A state should only be concerned with a well-defined part of the domain. Don’t try to centralize too much logic in one
state — prefer multiple focused states over a single “god object.”

### Ensure idempotency

A decision method should avoid applying duplicate events if called multiple times with the same input. Check whether
the state already reflects the desired outcome before applying an event:

  ```php
  if ($newCapacity === $this->capacity) {
      return; // No change needed
  }
  ```

This prevents unnecessary noise in the event stream.

### Throw meaningful exceptions

When a decision cannot be applied, throw a clear, domain-specific exception. This makes failures easier to understand,
test, and handle:

  ```php
  if ($newCapacity <= 0) {
      throw new CourseCapacityInvalidException();
  }
  ```

Avoid silent failures or generic error messages — every invalid condition is an opportunity to communicate intent.

# Handling events

As explained in the previous chapter, once a **State** is stored, the new events it produced are appended to the event
store and published to the event bus.

This chapter examines what happens next: how events are dispatched to handlers, and how applications can react to them
using projectors and processors.

## Event handlers

An **event handler** is a class responsible for reacting to events after they have been stored in the event store.
Handlers typically perform side effects such as updating read models (projections), sending notifications, or
interacting with external systems.

In Backslash, an event handler must implement the `EventHandlerInterface`:

```php
use Backslash\Event\RecordedEvent;

interface EventHandlerInterface
{
    public function handle(RecordedEvent $recordedEvent): void;
}
```

The `handle()` method receives a `RecordedEvent` object, which wraps the original domain event along with timestamp and
metadata.

Backslash provides the `EventHandlerTrait` to simplify event handler implementation. This trait automatically forwards
incoming events to dedicated methods based on their class names.

For each event type, the trait looks for a method named by prefixing handle to the event’s short class name. For
example, for the event class `CourseCapacityChangedEvent`, the trait will invoke the method
`handleCourseCapacityChangedEvent()`.

Here is an example of an event handler:

```php
use Backslash\Event\RecordedEvent;
use Backslash\EventBus\EventHandlerInterface;
use Backslash\EventBus\EventHandlerTrait;

class CourseProjector implements EventHandlerInterface
{
    use EventHandlerTrait;

    protected function handleCourseCapacityChangedEvent(
        CourseCapacityChangedEvent $event,
        RecordedEvent $recordedEvent,
    ): void {
        // Do something with the event
    }
}
```

This approach keeps event handlers clean and focused, with strongly typed methods tailored to each event type.

## Mapping events to handlers

Event handlers can handle multiple event types, and individual events can be handled by multiple handlers.

To specify which event handlers respond to which events, handlers must be subscribed to the `EventBus`.

This subscription typically happens during your application’s bootstrap phase:

```php
$eventBus = new EventBus();

$handler1 = new MyHandler1();
$handler2 = new MyHandler2();

$eventBus->subscribe(SomeEvent1::class, $handler1);
$eventBus->subscribe(SomeEvent2::class, $handler1);
$eventBus->subscribe(SomeEvent2::class, $handler2);
```

## Lazy loading event handlers

To avoid instantiating all event handlers upfront, Backslash provides an `EventHandlerProxy`. This proxy defers handler
creation until the first relevant event is published:

```php
$courseProjector = new CourseProjector();
$eventBus = new EventBus();

$eventBus->subscribe(CourseCapacityChangedEvent::class, new EventHandlerProxy(function() {
    return new CourseProjector();
}));
```

# Projecting read models

This section covers the most common type of event handling: `projecting read models`.

Read models, or projections, are optimized views of the system’s state, built specifically for querying. They are kept
up to date by reacting to domain events. Unlike state objects, projections are not used to make business decisions —
their sole purpose is to serve read operations efficiently.

In Backslash, projections are created using event handlers that listen to relevant events and update a dedicated
data store. They are typically called projectors.

The following sections will walk through how to build and organize projectors using event handlers, and how to keep read
models consistent with the event stream.

# Commands and command handling

Commands represent user intentions or requests to perform operations in your system. Unlike events, which describe what
has already happened, commands express what should happen.

## Defining commands

Commands are simple data transfer objects that carry the information needed to perform an operation. They don't need to
implement any specific interface, but following consistent patterns helps maintain clarity.

```php
readonly class ChangeCourseCapacityCommand
{
    public function __construct(
        public string $courseId,
        public int $capacity,
    ) {
    }
}
```

## Command handlers

Command handlers contain the business logic for processing commands. They coordinate between different states and
orchestrate the decision-making process.

```php
use Backslash\CommandBus\CommandHandlerInterface;
use Backslash\Event\Repository;

class ChangeCourseCapacityHandler implements CommandHandlerInterface
{
    public function __construct(
        private Repository $repository,
    ) {
    }

    public function handle(ChangeCapacityCommand $command): void
    {
        // Define the query to load relevant events
        $query = EventClass::in(
                CourseDefinedEvent::class,
                CourseCapacityChangedEvent::class,
            )
            ->and(
                Identifier::is('courseId', $command->courseId)
            );

        // Load the state
        $state = $this->repository->load(CourseCapacityState::class, $query);

        // Execute the business decision
        $state->changeCapacity($command->courseId, $command->newCapacity);

        // Persist new events
        $this->repository->store($state);
    }
}
```

### Handler responsibilities

Command handlers should:

- **Load the appropriate state(s)** using targeted queries
- **Delegate business decisions** to state objects
- **Handle repository operations** (load/store)
- **Manage transactions** when needed
- **Handle concurrency exceptions** appropriately

Command handlers should not:

- **Contain business logic** (delegate to states instead)
- **Directly manipulate events** (use states for that)
- **Perform side effects** (those should be triggered by events)

## Command dispatcher

The `Dispatcher` routes commands to their appropriate handlers. Set up the dispatcher during your application's
bootstrap phase:

```php
use Backslash\CommandBus\Dispatcher;

$dispatcher = new Dispatcher();

// Register command handlers
$dispatcher->register(
    ChangeCapacityCommand::class,
    new ChangeCourseCapacityHandler($repository)
);

$dispatcher->register(
    SubscribeStudentCommand::class,
    new SubscribeStudentHandler($repository)
);

// Dispatch a command
$command = new ChangeCapacityCommand('course-123', 50, 'user-456');
$dispatcher->dispatch($command);
```

### Lazy handler registration

To avoid instantiating all handlers upfront, you can use closures:

```php
$dispatcher->register(
    ChangeCapacityCommand::class,
    fn() => new ChangeCourseCapacityHandler($repository)
);
```

# Event persistence

# Testing scenarios

# Organizing your application

# Advanced topics

# Best practices

## Events

When modeling events in Backslash, following these best practices will help keep your system consistent, maintainable,
and easy to evolve:

- **Use descriptive, past-tense names**  
  Event names should clearly reflect something that has happened. For example, `StudentSubscribedToCourseEvent`, not
  `SubscribeStudent`.
- **Keep events immutable**  
  Declare properties as **readonly** or avoid modifying event data after creation to ensure event integrity.
- **Limit event data to what's necessary**  
  Include only the information needed to understand and replay the event. Avoid including derived or redundant data.
- **Use scalar types for attributes**  
  This enables easy serialization with `ToArrayTrait`.
- **Return all relevant identifiers in `getIdentifiers()`**  
  When your event relates to multiple entities (as with Dynamic Consistency Boundary), return all their identifiers here
  to properly scope consistency.
