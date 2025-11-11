---
title: "Querying Events"
weight: 6
bookToc: true
---
# Querying Events

Queries define which events should be loaded from the EventStore. They act as dynamic consistency boundaries, allowing
you to load exactly the events needed to make a specific decision. The boundaries are determined at runtime based on the
operation being performed, not predefined at design time.

Queries also play a crucial role in optimistic concurrency control. Before appending new events, the query is
re-executed to verify that no other process has added events to the stream in the meantime.

## Understanding queries

Queries implement `QueryInterface` and describe which events to fetch based on event class and identifiers.

Here are some basic query examples:

```php
// Load all events of a specific type
EventClass::is(StudentRegisteredEvent::class)

// Load events for a specific entity
Identifier::is('studentId', 'student-123')

// Combine filters
EventClass::is(StudentRegisteredEvent::class)
    ->and(Identifier::is('studentId', 'student-123'))
```

The demo includes a static method on each Model to build its query. This is a convenient convention but not required by
Backslash; queries can be created anywhere in your application.

Here's a simple case for `CourseCapacityModel`:

```php
public static function buildQuery(string $courseId): QueryInterface
{
    return EventClass::in(
        CourseCapacityChangedEvent::class,
        CourseDefinedEvent::class,
    )->and(Identifier::is('courseId', $courseId));
}
```

This query loads events related to a single course's capacity.

## Building queries with EventClass and Identifier

Queries combine event class filters with identifier filters:

**EventClass filters:**

- `EventClass::is(EventClass::class)` - Single event type
- `EventClass::in(Event1::class, Event2::class)` - Multiple event types

**Identifier filters:**

- `Identifier::is('key', 'value')` - Exact identifier match
- Multiple identifiers can be combined

**Combining filters:**

- `->and()` - Both conditions must match
- `->or()` - Either condition must match

## Building multi-entity queries

The `CourseSubscriptionModel` demonstrates a more complex query spanning multiple entities:

```php
public static function buildQuery(string $studentId, string $courseId): QueryInterface
{
    $eventForThisCourseLifecycle = EventClass::in(
        CourseCapacityChangedEvent::class,
        CourseDefinedEvent::class,
    )->and(Identifier::is('courseId', $courseId));

    $eventsForThisStudentLifecycle = EventClass::is(
        StudentRegisteredEvent::class,
    )->and(Identifier::is('studentId', $studentId));

    $eventsForThisStudentSubscriptions = EventClass::in(
        StudentUnsubscribedFromCourseEvent::class,
        StudentSubscribedToCourseEvent::class,
    )->and(Identifier::is('studentId', $studentId));

    $eventsForSubscriptionsToThisCourse = EventClass::in(
        StudentSubscribedToCourseEvent::class,
        StudentUnsubscribedFromCourseEvent::class,
    )->and(Identifier::is('courseId', $courseId));

    return $eventForThisCourseLifecycle
        ->or($eventsForThisStudentLifecycle)
        ->or($eventsForThisStudentSubscriptions)
        ->or($eventsForSubscriptionsToThisCourse);
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
