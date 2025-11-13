---
title: "Building Models"
weight: 6
---

Models are decision-making components that encapsulate domain logic. They are rebuilt from events and record new events
when business rules are satisfied.

## Introducing the Repository

Before diving into models, it's important to understand the `Repository`. The Repository is a core Backslash component
responsible for:

- Loading events based on a query
- Replaying those events into a model
- Persisting new events recorded by the model
- Publishing events to the EventBus

You'll use the Repository in command handlers like this:

```php
// Load a model using a query
$model = $repository->loadModel(ModelClass::class, $query);

// Execute business logic
$model->makeDecision();

// Persist changes
$repository->storeChanges($model);
```

## Creating model classes

Models extend `AbstractModel`, which implements `ModelInterface`:

```php
interface ModelInterface
{
    public function getChanges(): RecordedEventStream;
    public function clearChanges(): void;
    public function applyEvents(RecordedEventStream $stream): void;
}
```

These methods allow the Repository to:

- Load events into the model with `applyEvents()`
- Retrieve new events recorded by the model with `getChanges()`
- Clear recorded events after persistence with `clearChanges()`

Here's an example of a model extending `AbstractModel`:

```php
use Backslash\Model\AbstractModel;

class CourseCapacityModel extends AbstractModel
{
    private ?string $courseId = null;
    private int $capacity = 0;
    private bool $defined = false;

    public function change(int $newCapacity): void
    {
        if (!$this->defined) {
            throw new CourseNotDefinedException();
        }

        if ($newCapacity === $this->capacity) {
            return; // Idempotent
        }

        $this->record(new CourseCapacityChangedEvent(
            courseId: $this->courseId,
            old: $this->capacity,
            new: $newCapacity
        ));
    }

    protected function applyCourseDefinedEvent(CourseDefinedEvent $event): void
    {
        $this->courseId = $event->courseId;
        $this->capacity = $event->capacity;
        $this->defined = true;
    }

    protected function applyCourseCapacityChangedEvent(
        CourseCapacityChangedEvent $event
    ): void {
        $this->capacity = $event->new;
    }
}
```

## Replaying events with apply methods

Apply methods rebuild the model's state from events. They follow the naming pattern `apply{EventClassName}`:

```php
protected function applyCourseCapacityChangedEvent(
    CourseCapacityChangedEvent $event
): void {
    $this->capacity = $event->new;
}
```

Apply methods are invoked automatically during event replay. They update internal state based on event data without
performing validation or business logic. Think of them as pure state transitions.

## Creating decision methods

Public methods enforce business rules. They inspect internal state, validate input, check for idempotency, and record
new events using `$this->record()` when rules are satisfied:

```php
public function change(int $newCapacity): void
{
    // Validate preconditions
    if (!$this->defined) {
        throw new CourseNotDefinedException();
    }

    // Check idempotency
    if ($newCapacity === $this->capacity) {
        return;
    }

    // Record the decision
    $this->record(new CourseCapacityChangedEvent(
        courseId: $this->courseId,
        old: $this->capacity,
        new: $newCapacity
    ));
}
```

Decision methods express business logic clearly. They throw domain exceptions when rules are violated and record events
when decisions are valid.

## Recording new events

Use `$this->record()` to append new events:

```php
$this->record(new StudentRegisteredEvent(
    studentId: $studentId,
    name: $name
));
```

The `record()` method does two things:

1. Automatically calls the corresponding apply method, updating the model's state immediately
2. Adds the event to the model's internal list of changes

This means recorded events are:

1. Applied immediately to the model via apply methods
2. Returned by `getChanges()` for persistence
3. Not yet persisted until `Repository::storeChanges()` is called

Note that you use `$this->record()` rather than returning events because `AbstractModel` manages the internal event
stream. This design allows models to record multiple events for complex operations while maintaining clean method
signatures.

## Best practices

**Keep models focused.** Each model should enforce a specific set of business rules. Don't create god models that try to
enforce everything.

**Use domain exceptions.** When business rules are violated, throw descriptive exceptions that explain what went wrong.

**Check idempotency.** Before recording events, verify the operation isn't redundant.

**Apply methods are pure state transitions.** Never perform validation or business logic in apply methods; they only
update state.

**Decision methods contain business logic.** All validation, rule enforcement, and conditional logic belongs in public
decision methods.

**Record events, don't return them.** Use `$this->record()` to append events; don't return events from decision methods.

**Models are stateful during their lifetime.** A model instance retains its state throughout the request. Load it once,
make decisions, then store changes.

**One model instance per decision.** Don't reuse a model instance for multiple unrelated decisions. Load a fresh model
for each command.

---
