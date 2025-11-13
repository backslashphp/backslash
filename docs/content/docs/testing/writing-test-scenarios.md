---
title: "Writing Test Scenarios"
weight: 2
---

The `Scenario` component provides a fluent, behavior-driven interface for testing event-sourced applications. It uses
the `Play` class to define test scenarios in a readable, expressive manner.

## Understanding scenarios

Scenarios test the complete flow from command dispatch through event publication to projection updates. They verify that
commands produce the expected events and that projections are updated correctly.

The `Scenario` class was instantiated in your base test case (section 12). Each test creates `Play` instances and
executes them via `scenario->play()`.

A scenario can execute multiple plays sequentially, similar to acts in a theater piece. Each play represents a distinct
phase of the test, and subsequent plays see the effects of previous plays.

## Writing basic scenarios with Play

Create test scenarios using the `Play` class. A Play defines:

- **Initial state** via `withInitialEvents()` or `withInitialCommands()`
- **Actions** via `dispatch()` or `doAction()`
- **Assertions** via `testEvents()`, `testProjections()`, or `testThat()`
- **Expected exceptions** via `expectException()` or `expectExceptionMessage()`

The order of these methods matters: setup methods should come first, followed by actions, then assertions.

Here's a basic example:

```php
public function test_registering_student_publishes_event(): void
{
    $this->scenario->play(
        new Play()
            ->dispatch(new RegisterStudentCommand('1', 'John'))
            ->testEvents(function (PublishedEvents $events) {
                $this->assertPublishedEventsContainExactly([
                    StudentRegisteredEvent::class => 1,
                ], $events);
            })
    );
}
```

## Setting up initial state

Use `withInitialCommands()` to establish state by dispatching commands before the test action:

```php
$this->scenario->play(
    new Play()
        ->withInitialCommands(
            new DefineCourseCommand('123', 'PHP Basics', 10),
            new RegisterStudentCommand('1', 'Alice')
        )
        ->dispatch(new SubscribeStudentToCourseCommand('1', '123'))
        ->testEvents(function (PublishedEvents $events) {
            $this->assertPublishedEventsContain(
                StudentSubscribedToCourseEvent::class,
                $events
            );
        })
);
```

Commands passed to `withInitialCommands()` are dispatched before the main test action, setting up the required initial
state.

Use `withInitialEvents()` to directly publish events for initial state:

```php
use Backslash\Event\RecordedEventStream;

$initialEvents = new RecordedEventStream([
    new RecordedEvent(
        new CourseDefinedEvent('123', 'PHP Basics', 10),
        new Metadata([]),
        new DateTimeImmutable()
    ),
]);

$this->scenario->play(
    new Play()
        ->withInitialEvents($initialEvents)
        ->dispatch(new ChangeCourseCapacityCommand('123', 20))
        ->testEvents(function (PublishedEvents $events) {
            $this->assertPublishedEventsContain(
                CourseCapacityChangedEvent::class,
                $events
            );
        })
);
```

## Dispatching commands

Use `dispatch()` to execute one or more commands:

```php
$this->scenario->play(
    new Play()
        ->dispatch(
            new RegisterStudentCommand('1', 'John'),
            new RegisterStudentCommand('2', 'Alice')
        )
        ->testEvents(function (PublishedEvents $events) {
            $this->assertPublishedEventsCount(2, $events);
        })
);
```

Commands are dispatched through the normal command handling flow, including all middleware and handlers.

## Executing actions

Use `doAction()` to execute arbitrary code during the test. Common use cases include:

- Defining constants or variables needed by subsequent assertions
- Setting up mocks or stubs
- Manipulating time or other external dependencies

Example:

```php
$this->scenario->play(
    new Play()
        ->withInitialCommands(new DefineCourseCommand('123', 'PHP Basics', 10))
        ->doAction(function () {
            // Define a constant for use in later assertions
            define('MY_CONSTANT', 'some-value');
        })
        ->dispatch(new ChangeCourseCapacityCommand('123', 20))
        ->testEvents(function (PublishedEvents $events) {
            $this->assertPublishedEventsCount(1, $events);
        })
        ->testThat(function () {
            // Verify the constant was defined
            $this->assertTrue(defined('MY_CONSTANT'));
        })
);
```

Actions execute in the order they're defined relative to commands.

## Asserting on published events

Use `testEvents()` to verify which events were published:

```php
$this->scenario->play(
    new Play()
        ->dispatch(new RegisterStudentCommand('1', 'John'))
        ->testEvents(function (PublishedEvents $events) {
            // Assert specific event was published
            $this->assertPublishedEventsContain(
                StudentRegisteredEvent::class,
                $events
            );
            
            // Assert event count
            $this->assertPublishedEventsCount(1, $events);
            
            // Assert exact event types and counts
            $this->assertPublishedEventsContainExactly([
                StudentRegisteredEvent::class => 1,
            ], $events);
        })
);
```

The `testEvents()` callback receives a `PublishedEvents` object containing all events published **during this play only
**. Events from previous plays or initial setup are not included in this collection.

## Asserting on updated projections

Use `testProjections()` to verify which projections were updated:

```php
$this->scenario->play(
    new Play()
        ->withInitialCommands(
            new DefineCourseCommand('123', 'PHP Basics', 10),
            new RegisterStudentCommand('1', 'John')
        )
        ->dispatch(new SubscribeStudentToCourseCommand('1', '123'))
        ->testProjections(function (UpdatedProjections $projections) {
            // Assert specific projection was updated
            $this->assertUpdatedProjectionsContain(
                StudentListProjection::class,
                $projections
            );
            
            // Access updated projections
            $studentList = $projections->getAllOf(StudentListProjection::class)[0];
            $this->assertStringContainsString('John (PHP Basics)', (string) $studentList);
        })
);
```

The `testProjections()` callback receives an `UpdatedProjections` object containing projections modified **during this
play only**. Projections updated in previous plays are not included.

Use `getAllOf()` to retrieve all updated projections of a specific type. This returns an array because multiple
projection instances of the same class can be updated in a single play (for example, updating both `CourseProjection`
for course-123 and `CourseProjection` for course-456). Access individual projections by array index.

## Custom assertions

Use `testThat()` for custom assertion logic:

```php
$this->scenario->play(
    new Play()
        ->dispatch(new RegisterStudentCommand('1', 'John'))
        ->testThat(function () {
            $projection = $this->projectionStore->find(
                StudentListProjection::ID,
                StudentListProjection::class
            );
            $this->assertStringContainsString('John', (string) $projection);
        })
);
```

The `testThat()` callback executes after commands and actions, allowing arbitrary assertions.

## Testing business rule violations

Test that commands throw exceptions using `expectException()`:

```php
public function test_cannot_subscribe_to_full_course(): void
{
    $this->scenario->play(
        new Play()
            ->expectException(CourseAtFullCapacityException::class)
            ->withInitialCommands(
                new DefineCourseCommand('123', 'PHP Basics', 1),
                new RegisterStudentCommand('1', 'John'),
                new RegisterStudentCommand('2', 'Alice'),
                new SubscribeStudentToCourseCommand('1', '123')
            )
            ->dispatch(new SubscribeStudentToCourseCommand('2', '123'))
    );
}
```

When `expectException()` is used, the test passes only if the expected exception is thrown. Use the
`@doesNotPerformAssertions` PHPUnit annotation to avoid warnings; PHPUnit expects at least one assertion per test, but
with `expectException()` alone, PHPUnit doesn't recognize it as an assertion, so the annotation tells PHPUnit this is
intentional.

Test for specific exception messages using `expectExceptionMessage()`:

```php
$this->scenario->play(
    new Play()
        ->expectExceptionMessage('Course is at full capacity')
        ->withInitialCommands(
            new DefineCourseCommand('123', 'PHP Basics', 1),
            new RegisterStudentCommand('1', 'John'),
            new RegisterStudentCommand('2', 'Alice'),
            new SubscribeStudentToCourseCommand('1', '123')
        )
        ->dispatch(new SubscribeStudentToCourseCommand('2', '123'))
);
```

## Chaining multiple plays

A scenario can execute multiple plays sequentially, like acts in a theater piece. Each play represents a distinct phase
of the test:

```php
public function test_subscription_lifecycle(): void
{
    $subscribe = new Play()
        ->withInitialCommands(
            new DefineCourseCommand('123', 'PHP Basics', 10),
            new RegisterStudentCommand('1', 'John')
        )
        ->dispatch(new SubscribeStudentToCourseCommand('1', '123'))
        ->testEvents(function (PublishedEvents $events) {
            $this->assertPublishedEventsContain(
                StudentSubscribedToCourseEvent::class,
                $events
            );
        });

    $unsubscribe = new Play()
        ->dispatch(new UnsubscribeStudentFromCourseCommand('1', '123'))
        ->testEvents(function (PublishedEvents $events) {
            $this->assertPublishedEventsContain(
                StudentUnsubscribedFromCourseEvent::class,
                $events
            );
        });

    $this->scenario->play($subscribe, $unsubscribe);
}
```

Each play executes in order, like acts in a theater piece. The second play (unsubscribe) sees all the effects of the
first play (subscribe), including published events and updated projections. This allows you to test multi-step workflows
while keeping each phase clearly defined and testable.

## Complete scenario example

Here's a comprehensive example demonstrating all Play features and assertions:

```php
public function test_subscription_workflow(): void
{
    $studentId = '1';
    $courseId = '2';

    $subscribe = new Play()
        ->withInitialEvents(
            new RecordedEventStream(
                RecordedEvent::create(
                    new CourseDefinedEvent($courseId, 'Maths', 30),
                    new Metadata(),
                    new DateTimeImmutable(),
                ),
            ),
        )
        ->withInitialCommands(
            new RegisterStudentCommand($studentId, 'John'),
        )
        ->dispatch(
            new SubscribeStudentToCourseCommand($studentId, $courseId),
        )
        ->doAction(function (): void {
            define('MY_CONSTANT', 'some-value');
        })
        ->testEvents(function (PublishedEvents $events) use ($studentId, $courseId): void {
            $this->assertPublishedEventsCount(1, $events);
            $this->assertPublishedEventsContainOnly(StudentSubscribedToCourseEvent::class, $events);
            $this->assertPublishedEventsDoNotContain(StudentUnsubscribedFromCourseEvent::class, $events);

            /** @var StudentSubscribedToCourseEvent $event */
            $event = $events->getAllOf(StudentSubscribedToCourseEvent::class)[0]->getEvent();
            $this->assertEquals($studentId, $event->studentId);
            $this->assertEquals($courseId, $event->courseId);
        })
        ->testProjections(function (UpdatedProjections $projections): void {
            $this->assertUpdatedProjectionsCount(2, $projections);
            $this->assertUpdatedProjectionsContainExactly([
                CourseListProjection::class => 1,
                StudentListProjection::class => 1,
            ], $projections);
        })
        ->testThat(function (): void {
            defined('MY_CONSTANT');
        });

    $unsubscribe = new Play()
        ->dispatch(
            new UnsubscribeStudentFromCourseCommand($studentId, $courseId),
        )
        ->testEvents(function (PublishedEvents $events) use ($studentId, $courseId): void {
            $this->assertPublishedEventsContainExactly([
                StudentUnsubscribedFromCourseEvent::class => 1,
            ], $events);
        });

    $oops = new Play()
        ->expectException(StudentNotSubscribedToCourseException::class)
        ->dispatch(
            new UnsubscribeStudentFromCourseCommand($studentId, $courseId),
        );

    $this->scenario->play(
        $subscribe,
        $unsubscribe,
        $oops,
    );
}
```

This example demonstrates:

- **withInitialEvents()**: Setting up state with a `RecordedEventStream`
- **withInitialCommands()**: Dispatching commands for initial setup
- **dispatch()**: Executing the main test command
- **doAction()**: Running arbitrary code during the test
- **testEvents()**: Comprehensive event assertions including count, type, and content verification
- **testProjections()**: Verifying projection updates with exact counts
- **testThat()**: Custom assertion logic
- **expectException()**: Testing business rule violations
- **Multiple plays**: Three sequential plays representing different phases of the workflow

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

All Play methods return a new instance, allowing fluent chaining:

```php
$this->scenario->play(
    new Play()
        ->withInitialCommands(new DefineCourseCommand('123', 'PHP Basics', 10))
        ->dispatch(new ChangeCourseCapacityCommand('123', 20))
        ->testEvents(function (PublishedEvents $events) {
            $this->assertPublishedEventsCount(1, $events);
        })
        ->testProjections(function (UpdatedProjections $projections) {
            $this->assertUpdatedProjectionsContain(CourseProjection::class, $projections);
        })
        ->testThat(function () {
            $projection = $this->projectionStore->find('123', CourseProjection::class);
            $this->assertEquals(20, $projection->getCapacity());
        })
);
```

## Testing models in isolation

You can test models directly without scenarios for unit-level tests:

```php
public function test_course_capacity_model_enforces_rules(): void
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
`applyEvents()` with a `RecordedEventStream` to set up the model's initial state, just like `withInitialEvents()` in
scenarios.

## Best practices

**Set up state with withInitialCommands().** Use `withInitialCommands()` to establish initial state rather than
manipulating stores directly; this ensures events are published and projections are updated correctly.

**Keep scenarios focused.** Each test should verify one behavior; avoid testing multiple unrelated behaviors in a single
scenario.

**Test both success and failure.** Write scenarios for both successful operations and business rule violations using
`expectException()`.

**Leverage AssertionsTrait.** Use the provided assertions (`assertPublishedEventsContain()`,
`assertUpdatedProjectionsContain()`) rather than writing custom assertion logic.

**Chain multiple plays for workflows.** When testing multi-step processes, create separate plays for each step and
execute them together via `scenario->play($play1, $play2)`.

**Use @doesNotPerformAssertions for exception tests.** When using `expectException()` without additional assertions, add
the `@doesNotPerformAssertions` PHPUnit annotation to avoid warnings.

---
