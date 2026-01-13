---
title: "Reacting with Event Handlers"
weight: 3
---

Event handlers respond to events by updating projections, triggering side effects, or integrating with external systems.
They subscribe to specific event types and execute synchronously when those events are published.

## Creating event handlers

Event handlers implement `EventHandlerInterface`:

```php
interface EventHandlerInterface
{
    public function handle(RecordedEvent $recordedEvent): void;
}
```

Use `EventHandlerTrait` to automatically route events to methods based on class names:

```php
use Backslash\EventBus\EventHandlerInterface;
use Backslash\EventBus\EventHandlerTrait;

class NotificationHandler implements EventHandlerInterface
{
    use EventHandlerTrait;

    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    private function handleStudentSubscribedToCourseEvent(
        StudentSubscribedToCourseEvent $event,
        RecordedEvent $recordedEvent,
    ): void {
        $this->mailer->send(
            to: $event->studentId,
            subject: 'Course Subscription Confirmed',
            body: "You have been subscribed to course {$event->courseId}"
        );
    }
}
```

The trait looks for methods named by prefixing `handle` to the event's short class name. It automatically extracts the
domain event from the `RecordedEvent` wrapper and passes both to the handler method.

## Handler method signature

Handler methods follow this pattern:

```php
private function handleSomeEvent(
    SomeEvent $event,
    RecordedEvent $recordedEvent,
): void {
    // Handler logic
}
```

Requirements:

- Method must be `private` (or `protected` if creating an abstract base handler class)
- First parameter is the domain event with full type information
- Second parameter is the `RecordedEvent` wrapper (optional)
- Must return `void`

The `RecordedEvent` provides access to metadata and the recording timestamp. Most handlers only need the domain event;
use the second parameter when you need metadata like correlation IDs or user context:

```php
private function handleStudentSubscribedToCourseEvent(
    StudentSubscribedToCourseEvent $event,
    RecordedEvent $recordedEvent,
): void {
    $correlationId = $recordedEvent->getMetadata()->get('correlation_id');
    
    $this->logger->info('Student subscribed to course', [
        'correlation_id' => $correlationId,
        'student_id' => $event->studentId,
        'course_id' => $event->courseId,
        'timestamp' => $recordedEvent->getRecordTime()->format('Y-m-d H:i:s'),
    ]);
}
```

## Subscribing handlers to events

Register handlers with the EventBus during application bootstrap:

```php
$eventBus->subscribe(CourseDefinedEvent::class, $notificationHandler);
$eventBus->subscribe(StudentRegisteredEvent::class, $notificationHandler);
```

A single handler can respond to multiple event types, and multiple handlers can respond to the same event.

## Subscribing to all events

Use `subscribeAll()` to register a handler that receives every published event, regardless of type:

```php
$eventBus->subscribeAll($loggingHandler);
```

This is useful for cross-cutting concerns like global logging, audit trails, or monitoring. Handlers registered with
`subscribeAll()` execute after all event-specific handlers, ensuring they run last in the handler chain.

If a handler is registered both with `subscribe()` for specific events and with `subscribeAll()`, it will only be called
once per event to avoid duplicates.

**Example logging handler:**

```php
class EventLoggingHandler implements EventHandlerInterface
{
    use EventHandlerTrait;

    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function handle(RecordedEvent $recordedEvent): void
    {
        $event = $recordedEvent->getEvent();

        $this->logger->info('Event published', [
            'event_type' => $event::class,
            'event_data' => $event->toArray(),
            'recorded_at' => $recordedEvent->getRecordTime()->format('Y-m-d H:i:s'),
            'metadata' => $recordedEvent->getMetadata()->toArray(),
        ]);
    }
}
```

When using `subscribeAll()`, implement the `handle()` method directly instead of using the trait's automatic routing,
since you're handling all event types rather than specific ones.

## Handling multiple event types

A single handler class can respond to different events by implementing multiple handler methods:

```php
class CourseNotificationHandler implements EventHandlerInterface
{
    use EventHandlerTrait;

    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
    ) {
    }

    private function handleCourseDefinedEvent(
        CourseDefinedEvent $event,
        RecordedEvent $recordedEvent,
    ): void {
        $this->logger->info('New course defined', [
            'course_id' => $event->courseId,
            'name' => $event->name,
        ]);
    }

    private function handleCourseCapacityChangedEvent(
        CourseCapacityChangedEvent $event,
        RecordedEvent $recordedEvent,
    ): void {
        $this->mailer->sendToAdmins(
            subject: 'Course Capacity Changed',
            body: "Course {$event->courseId} capacity changed from {$event->previous} to {$event->new}"
        );
    }

    private function handleStudentSubscribedToCourseEvent(
        StudentSubscribedToCourseEvent $event,
        RecordedEvent $recordedEvent,
    ): void {
        $this->mailer->send(
            to: $event->studentId,
            subject: 'Subscription Confirmed',
            body: "You are now enrolled in course {$event->courseId}"
        );
    }
}
```

Register the handler for all relevant events:

```php
$handler = new CourseNotificationHandler($mailer, $logger);

$eventBus->subscribe(CourseDefinedEvent::class, $handler);
$eventBus->subscribe(CourseCapacityChangedEvent::class, $handler);
$eventBus->subscribe(StudentSubscribedToCourseEvent::class, $handler);
```

## Lazy loading handlers

Avoid instantiating all handlers during bootstrap using `EventHandlerProxy`:

```php
use Backslash\EventBus\EventHandlerProxy;

$eventBus->subscribe(
    CourseDefinedEvent::class,
    new EventHandlerProxy(fn() => $container->get(NotificationHandler::class))
);
```

The proxy defers handler instantiation until the first relevant event is published.

## Handler execution order

Multiple handlers subscribed to the same event execute synchronously in registration order:

```php
$eventBus->subscribe(StudentSubscribedToCourseEvent::class, $courseProjector);  // First
$eventBus->subscribe(StudentSubscribedToCourseEvent::class, $studentProjector); // Second
$eventBus->subscribe(StudentSubscribedToCourseEvent::class, $emailNotifier);    // Third
```

This synchronous execution ensures:

- Projections are updated before the command completes
- Exceptions propagate immediately
- Read-your-writes consistency within the same transaction

Event handlers execute after events are persisted to the EventStore but before the command handler returns. This
guarantees that projections reflect the latest state when the command completes successfully.

## Handling errors in event handlers

Event handlers can choose how to handle errors based on the operation's criticality.

**For critical operations** like projection updates, let exceptions propagate to halt processing:

```php
private function handleCourseDefinedEvent(
    CourseDefinedEvent $event,
    RecordedEvent $recordedEvent,
): void {
    $list = $this->getList();
    $list->defineCourse($event->courseId, $event->name, $event->capacity);
    
    // If storing fails, exception propagates and command fails
    $this->projections->store($list);
}
```

**For non-critical operations** like sending notifications, catch exceptions to prevent blocking:

```php
private function handleStudentSubscribedToCourseEvent(
    StudentSubscribedToCourseEvent $event,
    RecordedEvent $recordedEvent,
): void {
    try {
        $this->mailer->send(
            to: $event->studentId,
            subject: 'Subscription Confirmed',
            body: $this->buildMessage($event)
        );
    } catch (MailerException $e) {
        // Log but don't halt processing
        $this->logger->warning('Failed to send notification', [
            'event' => $event::class,
            'error' => $e->getMessage(),
        ]);
    }
}
```

## Best practices

**Keep handlers focused.** Each handler should have a single responsibility. Don't mix projection updates with external
API calls in the same handler.

**Handle missing data gracefully.** If a resource doesn't exist when handling an event, create it or handle the absence
appropriately rather than letting the handler fail.

**Make side effects idempotent.** External operations like sending emails should be idempotent or use deduplication
mechanisms to prevent duplicate actions during replays.

**Let critical failures propagate.** For essential operations like projection updates, let exceptions propagate to halt
processing.

**Catch non-critical failures.** For optional operations like notifications, catch exceptions and log them without
blocking the event flow.

**Use metadata when needed.** Access the `RecordedEvent` parameter to retrieve correlation IDs, timestamps, or other
metadata for logging and tracing.

Event handlers are essential for the read side of your CQRS architecture. The most common use of event handlers is
updating projections, which we'll explore in the next section.

---
