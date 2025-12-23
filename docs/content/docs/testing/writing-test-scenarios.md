---
title: "Writing Test Scenarios"
weight: 2
---

The `Scenario` component provides a fluent, behavior-driven interface for testing event-sourced applications. It uses
the `Play` class to define test scenarios in a readable, expressive Given-When-Then manner.

## Understanding scenarios

Scenarios test the complete flow from command dispatch through event publication to projection updates. They verify that
commands produce the expected events and that projections are updated correctly.

The `Scenario` class was instantiated in your base test case (section 12). Each test creates `Play` instances and
executes them via `scenario->play()`.

A scenario can execute multiple plays sequentially, similar to acts in a theater piece. Each play represents a distinct
phase of the test, and subsequent plays see the effects of previous plays.

## Writing basic scenarios with Play

Create test scenarios using the `Play` class with the Given-When-Then pattern. A Play defines:

- **Initial state (Given)** via `given()` - accepts events or commands
- **Actions (When)** via `when()` - accepts commands or closures
- **Assertions (Then)** via `then()` - automatic parameter routing based on type hints
- **Expected exceptions** via `thenExpectException()` or `thenExpectExceptionMessage()`

The order of these methods matters: setup methods should come first, followed by actions, then assertions.

Here's a basic example:

```php
#[Test]
public function it_publishes_event_when_registering_student(): void
{
    $this->scenario->play(
        new Play()
            ->when(new RegisterStudentCommand('1', 'John'))
            ->then(fn (PublishedEvents $events) =>
                $this->assertPublishedEventsContainExactly([
                    StudentRegisteredEvent::class => 1,
                ], $events)
            )
    );
}
```

## Setting up initial state with given()

Use `given()` to establish the initial state before executing your test action. The `given()` method accepts both events
and commands, making it flexible for different testing scenarios.

### Given with commands

Commands passed to `given()` are dispatched before the main test action, setting up the required initial state:

```php
$this->scenario->play(
    new Play()
        ->given(
            new DefineCourseCommand('123', 'PHP Basics', 10),
            new RegisterStudentCommand('1', 'Alice')
        )
        ->when(new SubscribeStudentToCourseCommand('1', '123'))
        ->then(fn (PublishedEvents $events) =>
            $this->assertPublishedEventsContain(
                StudentSubscribedToCourseEvent::class,
                $events
            )
        )
);
```

### Given with events

You can also use `given()` to directly set up state with events, which is useful when you want to bypass command
validation or set up specific event sequences:

```php
$this->scenario->play(
    new Play()
        ->given(
            new CourseDefinedEvent('123', 'PHP Basics', 10)
        )
        ->when(new ChangeCourseCapacityCommand('123', 20))
        ->then(fn (PublishedEvents $events) =>
            $this->assertPublishedEventsContain(
                CourseCapacityChangedEvent::class,
                $events
            )
        )
);
```

The `given()` method automatically wraps raw events in `RecordedEvent` instances with appropriate metadata.

## Executing actions with when()

The `when()` method is the heart of your test - it defines what action you're testing. It accepts both commands and
closures, giving you flexibility in how you trigger the behavior under test.

### When with commands

Pass commands to `when()` to execute them through the normal command handling flow:

```php
$this->scenario->play(
    new Play()
        ->when(
            new RegisterStudentCommand('1', 'John'),
            new RegisterStudentCommand('2', 'Alice')
        )
        ->then(fn (PublishedEvents $events) =>
            $this->assertPublishedEventsCount(2, $events)
        )
);
```

### When with closures

Use closures with `when()` to execute custom logic during the test. The closure can receive a `RepositoryInterface`
parameter for direct model manipulation:

```php
$this->scenario->play(
    new Play()
        ->given(new StudentRegisteredEvent('1', 'John'))
        ->when(function (RepositoryInterface $repo): void {
            $model = $repo->loadModel(
                Student::class,
                Identifier::is('studentId', '1')
            );
            $model->changeName('Jane');
            $repo->storeChanges($model);
        })
        ->then(fn (PublishedEvents $events) =>
            $this->assertNotEmpty($events->getAllOf(StudentNameChangedEvent::class))
        )
);
```

Closures can also be used for other purposes like defining constants or setting up test data:

```php
$this->scenario->play(
    new Play()
        ->given(new DefineCourseCommand('123', 'PHP Basics', 10))
        ->when(function (): void {
            define('MY_CONSTANT', 'some-value');
        })
        ->when(new ChangeCourseCapacityCommand('123', 20))
        ->then(fn () => $this->assertTrue(defined('MY_CONSTANT')))
);
```

Multiple `when()` calls execute in the order they're defined.

## Writing assertions with then()

The `then()` method is where you verify the results of your test. It supports automatic parameter routing based on type
hints, allowing you to assert on events, projections, or use the repository directly.

### Asserting on published events

Use `then()` with a `PublishedEvents` parameter to verify which events were published:

```php
$this->scenario->play(
    new Play()
        ->when(new RegisterStudentCommand('1', 'John'))
        ->then(fn (PublishedEvents $events) =>
            $this->assertPublishedEventsContainExactly([
                StudentRegisteredEvent::class => 1,
            ], $events)
        )
        ->then(function (PublishedEvents $events) {
            // You can also use a full closure for more complex assertions
            $this->assertPublishedEventsContain(
                StudentRegisteredEvent::class,
                $events
            );
            $this->assertPublishedEventsCount(1, $events);
        })
);
```

The `PublishedEvents` parameter contains all events published **during the when() phase only**. Events from the given()
phase are not included in this collection.

### Asserting on updated projections

Use `then()` with an `UpdatedProjections` parameter to verify which projections were updated:

```php
$this->scenario->play(
    new Play()
        ->given(
            new DefineCourseCommand('123', 'PHP Basics', 10),
            new RegisterStudentCommand('1', 'John')
        )
        ->when(new SubscribeStudentToCourseCommand('1', '123'))
        ->then(fn (UpdatedProjections $projections) =>
            $this->assertUpdatedProjectionsContain(
                StudentListProjection::class,
                $projections
            )
        )
        ->then(function (UpdatedProjections $projections) {
            // Access updated projections directly
            $studentList = $projections->getAllOf(StudentListProjection::class)[0];
            $this->assertStringContainsString('John (PHP Basics)', (string) $studentList);
        })
);
```

The `UpdatedProjections` parameter contains projections modified **during the when() phase only**. Projections updated
during given() are not included.

Use `getAllOf()` to retrieve all updated projections of a specific type. This returns an array because multiple
projection instances of the same class can be updated in a single play (for example, updating both `CourseProjection`
for course-123 and `CourseProjection` for course-456).

### Custom assertions with repository

Use `then()` with a `RepositoryInterface` parameter to load and verify model state directly:

```php
$this->scenario->play(
    new Play()
        ->when(new RegisterStudentCommand('1', 'John'))
        ->then(function (RepositoryInterface $repo) {
            $student = $repo->loadModel(
                Student::class,
                Identifier::is('studentId', '1')
            );
            $this->assertEquals('John', $student->getName());
        })
);
```

### Custom assertions without parameters

Use `then()` with no parameters for arbitrary assertion logic:

```php
$this->scenario->play(
    new Play()
        ->when(new RegisterStudentCommand('1', 'John'))
        ->then(function () {
            $projection = $this->projectionStore->find(
                StudentListProjection::ID,
                StudentListProjection::class
            );
            $this->assertStringContainsString('John', (string) $projection);
        })
);
```

All `then()` callbacks execute after the when() phase completes, and you can chain multiple `then()` calls with
different parameter types.

## Testing business rule violations

Test that commands throw exceptions using `thenExpectException()`:

```php
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;

#[Test]
#[DoesNotPerformAssertions]
public function it_prevents_subscription_to_full_course(): void
{
    $this->scenario->play(
        new Play()
            ->given(
                new DefineCourseCommand('123', 'PHP Basics', 1),
                new RegisterStudentCommand('1', 'John'),
                new RegisterStudentCommand('2', 'Alice'),
                new SubscribeStudentToCourseCommand('1', '123')
            )
            ->when(new SubscribeStudentToCourseCommand('2', '123'))
            ->thenExpectException(CourseAtFullCapacityException::class)
    );
}
```

When `thenExpectException()` is used, the test passes only if the expected exception is thrown during the when() phase.
Use the `#[DoesNotPerformAssertions]` PHPUnit attribute to avoid warnings about tests without assertions.

Test for specific exception messages using `thenExpectExceptionMessage()`:

```php
#[Test]
#[DoesNotPerformAssertions]
public function it_shows_helpful_error_message_for_full_course(): void
{
    $this->scenario->play(
        new Play()
            ->given(
                new DefineCourseCommand('123', 'PHP Basics', 1),
                new RegisterStudentCommand('1', 'John'),
                new RegisterStudentCommand('2', 'Alice'),
                new SubscribeStudentToCourseCommand('1', '123')
            )
            ->when(new SubscribeStudentToCourseCommand('2', '123'))
            ->thenExpectExceptionMessage('Course is at full capacity')
    );
}
```

## Chaining multiple plays

A scenario can execute multiple plays sequentially, like acts in a theater piece. Each play represents a distinct phase
of the test:

```php
#[Test]
public function it_handles_complete_subscription_lifecycle(): void
{
    $subscribe = new Play()
        ->given(
            new DefineCourseCommand('123', 'PHP Basics', 10),
            new RegisterStudentCommand('1', 'John')
        )
        ->when(new SubscribeStudentToCourseCommand('1', '123'))
        ->then(fn (PublishedEvents $events) =>
            $this->assertPublishedEventsContain(
                StudentSubscribedToCourseEvent::class,
                $events
            )
        );

    $unsubscribe = new Play()
        ->when(new UnsubscribeStudentFromCourseCommand('1', '123'))
        ->then(fn (PublishedEvents $events) =>
            $this->assertPublishedEventsContain(
                StudentUnsubscribedFromCourseEvent::class,
                $events
            )
        );

    $this->scenario->play($subscribe, $unsubscribe);
}
```

Each play executes in order, like acts in a theater piece. The second play (unsubscribe) sees all the effects of the
first play (subscribe), including published events and updated projections. This allows you to test multi-step workflows
while keeping each phase clearly defined and testable.

## Complete scenario example

Here's a comprehensive example demonstrating all Play features and assertions:

```php
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;

#[Test]
public function it_manages_complete_subscription_workflow(): void
{
    $studentId = '1';
    $courseId = '2';

    $subscribe = new Play()
        ->given(
            new CourseDefinedEvent($courseId, 'Maths', 30),
            new RegisterStudentCommand($studentId, 'John'),
        )
        ->when(new SubscribeStudentToCourseCommand($studentId, $courseId))
        ->when(function (): void {
            define('MY_CONSTANT', 'some-value');
        })
        ->then(function (PublishedEvents $events) use ($studentId, $courseId): void {
            $this->assertPublishedEventsCount(1, $events);
            $this->assertPublishedEventsContainOnly(StudentSubscribedToCourseEvent::class, $events);
            $this->assertPublishedEventsDoNotContain(StudentUnsubscribedFromCourseEvent::class, $events);

            /** @var StudentSubscribedToCourseEvent $event */
            $event = $events->getAllOf(StudentSubscribedToCourseEvent::class)[0]->getEvent();
            $this->assertEquals($studentId, $event->studentId);
            $this->assertEquals($courseId, $event->courseId);
        })
        ->then(fn (UpdatedProjections $projections) =>
            $this->assertUpdatedProjectionsContainExactly([
                CourseListProjection::class => 1,
                StudentListProjection::class => 1,
            ], $projections)
        )
        ->then(fn () => $this->assertTrue(defined('MY_CONSTANT')));

    $unsubscribe = new Play()
        ->when(new UnsubscribeStudentFromCourseCommand($studentId, $courseId))
        ->then(fn (PublishedEvents $events) =>
            $this->assertPublishedEventsContainExactly([
                StudentUnsubscribedFromCourseEvent::class => 1,
            ], $events)
        );

    $oops = new Play()
        ->when(new UnsubscribeStudentFromCourseCommand($studentId, $courseId))
        ->thenExpectException(StudentNotSubscribedToCourseException::class);

    $this->scenario->play($subscribe, $unsubscribe, $oops);
}
```

This example demonstrates:

- **given()**: Setting up state with both events and commands
- **when()**: Executing commands and arbitrary actions
- **then()** with **PublishedEvents**: Comprehensive event assertions including count, type, and content verification
- **then()** with **UpdatedProjections**: Verifying projection updates with exact counts
- **then()** with **no parameters**: Custom assertion logic
- **thenExpectException()**: Testing business rule violations
- **Multiple plays**: Three sequential plays representing different phases of the workflow

## Optimizing test performance with event publishing modes

By default, events generated in both `given()` and `when()` phases are published to the event bus, which triggers
projection updates. This ensures backwards compatibility and predictable behavior. However, for better performance, you
can optimize when events are published.

### Automatic projection detection (DETECT mode)

The `EventPublishingMode::DETECT` mode automatically analyzes your test to determine if projections are needed:

- Events in `given()` are **NOT published** (test setup only)
- Events in `when()` are **published only if** your assertions reference `UpdatedProjections`
- More efficient as projections are only updated when actually needed for assertions

Enable this mode at the scenario level:

```php
use Backslash\Scenario\EventPublishingMode;

class StudentTest extends TestCase
{
    private Scenario $scenario;

    protected function setUp(): void
    {
        $this->scenario = new Scenario();
        $this->scenario->setEventPublishingMode(EventPublishingMode::DETECT);
    }
}
```

With DETECT mode enabled, this test will NOT publish events during `given()`, and WILL publish during `when()` because
it asserts on projections:

```php
#[Test]
public function it_updates_student_projection(): void
{
    $this->scenario->play(
        new Play()
            ->given(new StudentRegisteredEvent('1', 'John'))  // NOT published
            ->when(function (RepositoryInterface $repo): void {
                $student = $repo->loadModel(Student::class, Identifier::is('studentId', '1'));
                $student->changeName('Jane');
                $repo->storeChanges($student);
            })  // WILL be published because of UpdatedProjections below
            ->then(fn (UpdatedProjections $projections) =>
                $this->assertUpdatedProjectionsContain(StudentProjection::class, $projections)
            )
    );
}
```

### Manual control per play

You can override the detection behavior for specific plays:

```php
// Force event publishing during given() phase
$this->scenario->play(
    new Play()
        ->withEventPublishingDuringSetup()  // Events in given() WILL be published
        ->given(new StudentRegisteredEvent('1', 'John'))
        ->when(new ChangeNameCommand('1', 'Jane'))
        ->then(fn (PublishedEvents $events) => ...)
);

// Prevent event publishing during given() phase
$this->scenario->play(
    new Play()
        ->withoutEventPublishingDuringSetup()  // Events in given() will NOT be published
        ->given(new StudentRegisteredEvent('1', 'John'))
        ->when(new ChangeNameCommand('1', 'Jane'))
        ->then(fn (PublishedEvents $events) => ...)
);
```

### Available modes

The `EventPublishingMode` enum provides three modes:

- **ALWAYS** (default): Events are published in both `given()` and `when()` phases, ensuring projections are always
  updated. Guarantees backwards compatibility.
- **DETECT**: Automatically determines if projections are needed by analyzing your `then()` assertions. Events in
  `given()` are not published; events in `when()` are published only if needed.
- **NEVER**: Events are never published to the event bus. Use this for pure event store testing without any projection
  updates.

Choose DETECT mode for better test performance when you don't need projections updated during setup, or stick with
ALWAYS for simpler, more predictable behavior.

## Using AssertionsTrait

The `AssertionsTrait` (included in your base test case) provides helpful assertions for working with scenarios:

**Event assertions:**

```php
// Assert an event was published
$this->assertPublishedEventsContain(CourseDefinedEvent::class, $events);

// Assert only a specific event type was published
$this->assertPublishedEventsContainOnly(CourseDefinedEvent::class, $events);

// Assert exact count of specific events
$this->assertPublishedEventsContainExactly([
    CourseDefinedEvent::class => 1,
    CourseCapacityChangedEvent::class => 2,
], $events);

// Assert total event count
$this->assertPublishedEventsCount(3, $events);

// Assert an event was NOT published
$this->assertPublishedEventsDoNotContain(CourseDeletedEvent::class, $events);
```

**Projection assertions:**

```php
// Assert a projection was updated
$this->assertUpdatedProjectionsContain(CourseProjection::class, $projections);

// Assert only a specific projection type was updated
$this->assertUpdatedProjectionsContainOnly(CourseProjection::class, $projections);

// Assert exact count of updated projections
$this->assertUpdatedProjectionsContainExactly([
    CourseProjection::class => 1,
    StudentProjection::class => 2,
], $projections);

// Assert total projection update count
$this->assertUpdatedProjectionsCount(3, $projections);
```

## Chaining Play methods

All Play methods return a new instance, allowing fluent chaining in the Given-When-Then pattern:

```php
#[Test]
public function it_changes_course_capacity_and_updates_projection(): void
{
    $this->scenario->play(
        new Play()
            ->given(new DefineCourseCommand('123', 'PHP Basics', 10))
            ->when(new ChangeCourseCapacityCommand('123', 20))
            ->then(fn (PublishedEvents $events) =>
                $this->assertPublishedEventsCount(1, $events)
            )
            ->then(fn (UpdatedProjections $projections) =>
                $this->assertUpdatedProjectionsContain(CourseProjection::class, $projections)
            )
            ->then(function () {
                $projection = $this->projectionStore->find('123', CourseProjection::class);
                $this->assertEquals(20, $projection->getCapacity());
            })
    );
}
```

## Testing models in isolation

You can test models directly without scenarios for unit-level tests:

```php
use PHPUnit\Framework\Attributes\Test;

#[Test]
public function it_enforces_course_capacity_rules(): void
{
    $model = new CourseCapacityModel();

    // Apply initial state
    $initialEvents = new RecordedEventStream(
        RecordedEvent::create(
            new CourseDefinedEvent('course-123', 'PHP Basics', 10),
            new Metadata(),
            new DateTimeImmutable()
        )
    );
    $model->applyEvents($initialEvents);

    // Execute business logic
    $model->change(20);

    // Assert recorded events
    $changes = $model->getChanges();
    $this->assertCount(1, $changes);

    $recordedEvent = iterator_to_array($changes)[0];
    $this->assertInstanceOf(CourseCapacityChangedEvent::class, $recordedEvent->getEvent());
}
```

This approach tests model logic in isolation without involving the command dispatcher, event bus, or projections. Use
`applyEvents()` with a `RecordedEventStream` to set up the model's initial state, just like `given()` with events in
scenarios.

## Best practices

**Use the Given-When-Then pattern.** Structure your tests with `given()` for setup, `when()` for actions, and `then()`
for assertions. This makes tests readable and self-documenting.

**Set up state with given().** Use `given()` to establish initial state with both events and commands. This ensures a
clean separation between setup and the actual test action.

**Keep scenarios focused.** Each test should verify one behavior; avoid testing multiple unrelated behaviors in a single
scenario. Name tests with `it_` prefix describing the expected behavior (e.g., `it_publishes_event_when_registering_student`).

**Test both success and failure.** Write scenarios for both successful operations and business rule violations using
`thenExpectException()`.

**Leverage AssertionsTrait.** Use the provided assertions (`assertPublishedEventsContain()`,
`assertUpdatedProjectionsContain()`) rather than writing custom assertion logic.

**Chain multiple plays for workflows.** When testing multi-step processes, create separate plays for each step and
execute them together via `scenario->play($play1, $play2)`.

**Use #[DoesNotPerformAssertions] for exception tests.** When using `thenExpectException()` without additional
assertions, add the `#[DoesNotPerformAssertions]` PHPUnit attribute to avoid warnings about tests without assertions.

**Use #[Test] attribute.** Prefer the modern `#[Test]` attribute over the `test_` prefix or `@test` docblock annotation
for marking test methods.

**Optimize with DETECT mode.** For better test performance, enable `EventPublishingMode::DETECT` at the scenario level
to avoid unnecessary projection updates during setup. Only use ALWAYS mode when you need projections updated during `given()`.

**Type-hint then() parameters.** Always type-hint the parameter in `then()` callbacks to enable automatic routing:
`then(fn (PublishedEvents $events) => ...)` instead of `then(fn ($events) => ...)`.

---
