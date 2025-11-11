---
title: "Defining Events"
weight: 5
bookToc: true
---
# Defining Events

Events represent immutable facts about what happened in your system. They are the building blocks of event sourcing and
capture domain changes as a permanent record.

## Creating event classes

In Backslash, events are simple classes that implement the `EventInterface`:

```php
readonly class StudentRegisteredEvent implements EventInterface
{
    use ToArrayTrait; // Handles automatic serialization

    public function __construct(
        public string $studentId,
        public string $name,
    ) {
    }

    public function getIdentifiers(): Identifiers
    {
        return new Identifiers([
            'studentId' => $this->studentId,
        ]);
    }
}
```

This is a simple single-entity model. For more complex scenarios, see `CourseSubscriptionModel` in the demo application,
which demonstrates a multi-entity model that loads events from both students and courses to enforce rules that span
multiple entities.

The `readonly` keyword is recommended to enforce immutability; once created, the event cannot be modified.

## Implementing the EventInterface

All events must implement `Backslash\Event\EventInterface`:

```php
interface EventInterface
{
    public function getIdentifiers(): Identifiers;
    public function toArray(): array;
    public static function fromArray(array $data): self;
}
```

The `getIdentifiers()` method returns identifiers used to scope the event. The `toArray()` and `fromArray()` methods
handle serialization for storage and retrieval.

## Working with identifiers

Backslash events can include identifiers for multiple entities, following the Dynamic Consistency Boundary
specification. Identifiers determine which events belong together when enforcing business rules.

The `StudentSubscribedToCourseEvent` includes identifiers for both student and course because the subscription action
involves both entities:

```php
public function getIdentifiers(): Identifiers
{
    return new Identifiers([
        'studentId' => $this->studentId,
        'courseId' => $this->courseId,
    ]);
}
```

This multi-entity identification enables dynamic consistency boundaries. When enforcing the rule "a student can only
subscribe if the course isn't full", you need events from both the student and the course within the same consistency
boundary.

Compare this with `CourseDefinedEvent`, which only identifies the course:

```php
readonly class CourseDefinedEvent implements EventInterface
{
    use ToArrayTrait;

    public function __construct(
        public string $courseId,
        public string $name,
        public int $capacity,
    ) {
    }

    public function getIdentifiers(): Identifiers
    {
        return new Identifiers([
            'courseId' => $this->courseId,
        ]);
    }
}
```

Different events need different identifiers depending on which entities participated in the action and which consistency
boundaries they support.

## Serializing events

Backslash provides `ToArrayTrait` that uses reflection to automatically implement `toArray()` and `fromArray()`. All
properties must be scalar types (`string`, `int`, `float`, `bool`, or `null`) or arrays of scalar types. Constructor
parameters must match the properties exactly by name and type.

```php
$event = new StudentSubscribedToCourseEvent('123', '456');
$serialized = $event->toArray();
// ['studentId' => '123', 'courseId' => '456']

$restored = StudentSubscribedToCourseEvent::fromArray($serialized);
```

For events with value objects or complex structures, implement `toArray()` and `fromArray()` manually:

```php
readonly class CourseScheduleChangedEvent implements EventInterface
{
    public function __construct(
        public string $courseId,
        public DateTimeImmutable $startDate,
        public DateTimeImmutable $endDate,
    ) {
    }

    public function getIdentifiers(): Identifiers
    {
        return new Identifiers([
            'courseId' => $this->courseId,
        ]);
    }

    public function toArray(): array
    {
        return [
            'courseId' => $this->courseId,
            'startDate' => $this->startDate->format('Y-m-d'),
            'endDate' => $this->endDate->format('Y-m-d'),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['courseId'],
            new DateTimeImmutable($data['startDate']),
            new DateTimeImmutable($data['endDate']),
        );
    }
}
```

## Best practices

**Use past tense for event names.** Events describe completed actions: `StudentRegisteredEvent`, not
`RegisterStudentEvent`; `CourseCapacityChangedEvent`, not `ChangeCourseCapacityEvent`.

**Keep events immutable.** Mark classes as `readonly` and use public readonly properties, or use private properties with
public getters. Never modify an event after creation.

**Include all relevant identifiers.** Add identifiers for every entity that participated in the event, not just a single
aggregate root.

**Store facts, not derived data.** Events should contain essential information about what happened, not calculations or
data derivable from other events.

**Keep events focused.** Each event should represent a single fact. Avoid capturing multiple unrelated changes in one
event.

**Consider recording both old and new values.** For state changes, recording previous and new values provides a complete
audit trail.

**Make serialization explicit for complex types.** If your event contains value objects or non-scalar types, implement
`toArray()` and `fromArray()` manually rather than relying on automatic serialization.

---
