---
title: "Querying Events"
weight: 5
---

Queries define which events should be loaded from the EventStore. They act as dynamic consistency boundaries, allowing
you to load exactly the events needed to make a specific decision. The boundaries are determined at runtime based on the
operation being performed, not predefined at design time.

Queries also play a crucial role in optimistic concurrency control. Before appending new events, the query is
re-executed to verify that no other process has added events to the stream in the meantime.

## Understanding queries

Queries are built with the `Query` class and describe which events to fetch based on event class and identifiers. This
follows the [Dynamic Consistency Boundary](https://dcb.events/specification/#query) specification: a `Query` is a set of
items combined with **OR**, and within a single item, event classes are combined with **OR** while identifiers are
combined with **AND**.

Here are some basic query examples:

```php
// Load all events of a specific type
(new Query())->withItem(EventClass::in(StudentRegisteredEvent::class))

// Load events for a specific entity
(new Query())->withItem(Identifier::is('studentId', 'student-123'))

// Combine filters (AND, within the same item)
(new Query())->withItem(
    EventClass::in(StudentRegisteredEvent::class),
    Identifier::is('studentId', 'student-123'),
)

// Load every event
new Query()
```

The demo includes a static method on each Model to build its query. This is a convenient convention but not required by
Backslash; queries can be created anywhere in your application.

Here's a simple case for `CourseCapacityModel`:

```php
public static function buildQuery(string $courseId): Query
{
    return (new Query())->withItem(
        EventClass::in(
            CourseCapacityChangedEvent::class,
            CourseDefinedEvent::class,
        ),
        Identifier::is('courseId', $courseId),
    );
}
```

This query loads events related to a single course's capacity.

## Building queries with EventClass and Identifier

A `->withItem(...)` call builds a single item; every filter passed to it applies to that same item. A `Query` with no
items at all (`new Query()` left as-is) matches every event; `->isMatchAll(): bool` tells you whether that's the case.

**EventClass filters:**

- `EventClass::in(Event1::class, Event2::class)` - One or more event types, combined with OR

**Identifier filters:**

- `Identifier::is('key', 'value')` - Exact identifier match
- Multiple identifiers passed to the same `->withItem(...)` call are combined with AND

**Combining items:**

- `->withItem(...)` - Adds another item to the query; an event matching any item is loaded

## Building multi-entity queries

The `CourseSubscriptionModel` demonstrates a more complex query spanning multiple entities:

```php
public static function buildQuery(string $studentId, string $courseId): Query
{
    return (new Query())
        ->withItem(
            EventClass::in(
                CourseCapacityChangedEvent::class,
                CourseDefinedEvent::class,
            ),
            Identifier::is('courseId', $courseId),
        )
        ->withItem(
            EventClass::in(StudentRegisteredEvent::class),
            Identifier::is('studentId', $studentId),
        )
        ->withItem(
            EventClass::in(
                StudentUnsubscribedFromCourseEvent::class,
                StudentSubscribedToCourseEvent::class,
            ),
            Identifier::is('studentId', $studentId),
        )
        ->withItem(
            EventClass::in(
                StudentSubscribedToCourseEvent::class,
                StudentUnsubscribedFromCourseEvent::class,
            ),
            Identifier::is('courseId', $courseId),
        );
}
```

This query loads:

- The course's lifecycle events (definition and capacity changes)
- The student's registration
- All of this student's subscription events
- All subscription events for this course (to count enrollments)

This multi-entity boundary enables the Model to enforce rules like "a student can only subscribe if the course isn't
full" and "a student can subscribe to at most 3 courses".

## Best practices

**Build queries per decision.** Each business decision should define its own query that loads exactly the events needed.

**Start narrow, expand as needed.** Begin with the minimal set of events and expand the query only when business rules
require additional context.

**Document complex queries.** Multi-entity queries can be intricate; add comments explaining what events are loaded and
why.

**Use static factory methods.** Place query building logic in static methods on your models for discoverability.

**Keep queries focused.** Don't load events that aren't needed for the decision being made.

---
