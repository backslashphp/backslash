---
title: "Building Projections"
weight: 11
bookToc: true
---
# Building Projections

Projections are read-optimized views of your system's state. Unlike Models, which are rebuilt from events to make
decisions, projections are designed specifically for querying. They represent the read side of your CQRS architecture.

**Projections** are data structures (read models) that represent the current state; **projectors** are event handlers
that update these projections when events occur. This separation keeps projection logic organized and testable.

Projections work with the `ProjectionStore`, which handles their persistence and retrieval. We'll cover the
`ProjectionStore` in detail later in this section.

## Understanding projections

A projection is a denormalized view built from events. It answers specific questions about your system's state without
requiring complex joins or event replay. For example:

- A list of all courses with their current capacity and available seats
- A student's enrollment history with course details
- A dashboard showing real-time subscription metrics

Projections are updated synchronously after events are published. Because event handlers execute in the same PHP process
as command dispatch, projections are immediately consistent with the write side once the command completes.

Projections are often designed for specific UI needs. They can expose their data in convenient formats like arrays for
JSON serialization or XML objects for API responses. This flexibility allows you to tailor each projection to its
intended consumer.

## Projections vs Models

The distinction between projections and models is fundamental to CQRS:

**Models (write side):**

- Rebuilt from events for each decision
- Enforce business rules
- Not persisted; exist only during command processing
- Optimized for making decisions

**Projections (read side):**

- Continuously updated as events occur
- Persisted for efficient querying
- No business logic; simple data structures
- Optimized for reading and display

## Defining projections

Projections must implement `ProjectionInterface`:

```php
interface ProjectionInterface
{
    public function getId(): string;
}
```

The `getId()` method returns the projection's identifier. A projection's identity is composed of both its ID and its
fully qualified class name, which means two different projection classes can use the same ID without conflict.

Here's a simple projection example:

```php
class CourseProjection implements ProjectionInterface
{
    private string $courseId;
    
    private string $name;

    private int $capacity;

    public function __construct(string $courseId, string $name, int $capacity)
    {
        $this->courseId = $courseId;
        $this->name = $name;
        $this->capacity = $capacity;
    }
    
    public function getId(): string
    {
        return $this->courseId;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function changeCapacity(int $newCapacity): void
    {
        $this->capacity = $newCapacity;
    }
}
```

This projection represents a course with methods to access its data and update its capacity. Each course gets its own
projection instance identified by its course ID.

## Exposing projection data

Projections often need to expose their data in formats suitable for APIs or user interfaces. Implement
`JsonSerializable` to control JSON representation:

```php
class CourseProjection implements ProjectionInterface, JsonSerializable
{
    private string $courseId;
    
    private string $name;

    private int $capacity;

    public function __construct(string $courseId, string $name, int $capacity)
    {
        $this->courseId = $courseId;
        $this->name = $name;
        $this->capacity = $capacity;
    }
    
    public function getId(): string
    {
        return $this->courseId;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function changeCapacity(int $newCapacity): void
    {
        $this->capacity = $newCapacity;
    }
    
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->courseId,
            'name' => $this->name,
            'capacity' => $this->capacity,
        ];
    }
}
```

This allows seamless JSON encoding:

```php
$projection = $projectionStore->find('course-123', CourseProjection::class);
$json = json_encode($projection);
// {"id":"course-123","name":"PHP Basics","capacity":30}
```

Similarly, projections can provide methods for XML, CSV, or any other format your application needs:

```php
public function toXml(): SimpleXMLElement
{
    $xml = new SimpleXMLElement('<course/>');
    $xml->addChild('id', $this->courseId);
    $xml->addChild('name', $this->name);
    $xml->addChild('capacity', (string) $this->capacity);
    return $xml;
}

public function toArray(): array
{
    return [
        'id' => $this->courseId,
        'name' => $this->name,
        'capacity' => $this->capacity,
    ];
}
```

## Designing projection structure

Design your projections based on how you need to query them, not how your domain is modeled. A single domain concept
might have multiple projections for different use cases.

Multiple specialized projections may seem redundant, but they enable optimal query performance; each projection can be
indexed and structured for its specific access pattern.

```php
// For listing all courses
class CourseListItemProjection implements ProjectionInterface
{
    public function __construct(
        public string $id,
        public string $name,
        public int $availableSeats,
    ) {
    }
    
    public function getId(): string
    {
        return $this->id;
    }
}

// For displaying course details
class CourseDetailProjection implements ProjectionInterface
{
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public int $capacity,
        public int $subscribedCount,
        public array $subscribedStudents,
    ) {
    }
    
    public function getId(): string
    {
        return $this->id;
    }
}

// For analytics
class CourseMetricsProjection implements ProjectionInterface
{
    public function __construct(
        public string $id,
        public int $totalSubscriptions,
        public int $totalUnsubscriptions,
        public float $averageSubscriptionDuration,
    ) {
    }
    
    public function getId(): string
    {
        return $this->id;
    }
}
```

Each projection is tailored to answer specific queries efficiently.

## Working with ProjectionStore

The `ProjectionStore` is responsible for persisting and retrieving projections. It uses an adapter to interact with the
underlying storage mechanism.

Backslash provides two built-in adapters:

- **PdoProjectionStoreAdapter**: For PDO-compatible databases (MySQL, SQLite); suitable for production use
- **InMemoryProjectionStoreAdapter**: In-memory storage; useful for testing with the `Scenario` component

Developers can build custom adapters for other storage engines like MongoDB, Redis, or even a file system by
implementing the `AdapterInterface`.

## Setting up the ProjectionStore

Create a `ProjectionStore` instance with a storage adapter during your application's bootstrap:

```php
$pdo = new PdoProxy(fn() => new PDO('mysql:host=localhost;dbname=myapp', 'user', 'pass'));
$serializer = new Serializer(new SerializeFunctionSerializer());
$config = new Config();

$adapter = new PdoProjectionStoreAdapter($pdo, $serializer, $config);
$projectionStore = new ProjectionStore($adapter);
```

The `PdoProjectionStoreAdapter` requires:

- **PDO connection**: Database connection via `PdoProxy` or direct `PDO` instance
- **Serializer**: Converts projections to/from storage format; `SerializeFunctionSerializer` uses PHP's native
  `serialize()` function
- **Config** (optional): Allows customization of database column names via `withTable()` and `withAlias()` methods

Example with custom configuration:

```php
$config = (new Config())
    ->withTable('my_projections')
    ->withAlias('projection_id', 'id')
    ->withAlias('projection_class', 'type')
    ->withAlias('projection_payload', 'data');

$adapter = new PdoProjectionStoreAdapter($pdo, $serializer, $config);
```

For testing, use the `InMemoryProjectionStoreAdapter`:

```php
$adapter = new InMemoryProjectionStoreAdapter();
$projectionStore = new ProjectionStore($adapter);
```

This adapter stores projections in memory, making it fast and isolated for test scenarios.

## Committing automatically after command handling

Backslash provides `ProjectionStoreTransactionCommandDispatcherMiddleware`, a command dispatcher middleware that manages
projection transactions automatically. It wraps the command dispatch process in a transaction and calls `commit()` on
the `ProjectionStore` when the command completes successfully. If an exception occurs during command processing, the
middleware calls `rollback()` to discard buffered changes.

```php
use Backslash\ProjectionStoreTransactionCommandDispatcherMiddleware\ProjectionStoreTransactionCommandDispatcherMiddleware;

$dispatcher->addMiddleware(
    new ProjectionStoreTransactionCommandDispatcherMiddleware($projectionStore)
);
```

This middleware is typically added to the command dispatcher during application bootstrap, ensuring all commands benefit
from automatic transaction management without requiring explicit commit/rollback calls in command handlers.

## Creating projectors

Projectors are event handlers that update projections. Here's a complete example:

```php
class CourseProjector implements EventHandlerInterface
{
    use EventHandlerTrait;

    public function __construct(
        private ProjectionStoreInterface $projections,
    ) {
    }

    private function handleCourseDefinedEvent(
        CourseDefinedEvent $event,
        RecordedEvent $recordedEvent,
    ): void {
        $projection = new CourseProjection(
            $event->courseId,
            $event->name,
            $event->capacity
        );
        
        $this->projections->store($projection);
    }

    private function handleCourseCapacityChangedEvent(
        CourseCapacityChangedEvent $event,
        RecordedEvent $recordedEvent,
    ): void {
        try {
            $projection = $this->projections->find(
                $event->courseId,
                CourseProjection::class
            );
            
            $projection->changeCapacity($event->new);
            $this->projections->store($projection);
        } catch (ProjectionNotFoundException) {
            // Log or handle missing projection
        }
    }
}
```

Register the projector with the EventBus:

```php
$projector = new CourseProjector($projectionStore);

$eventBus->subscribe(CourseDefinedEvent::class, $projector);
$eventBus->subscribe(CourseCapacityChangedEvent::class, $projector);
```

## Querying projections

Once projections are persisted, you can query them in your application or UI layer:

```php
// In a request handler or controller
public function handle(ServerRequestInterface $request): ResponseInterface
{
    $courseId = $request->getAttribute('courseId');
    $projection = $this->projectionStore->find($courseId, CourseProjection::class)
    return new JsonResponse($projection);
}
```

This separation between write operations (commands updating models) and read operations (queries fetching projections)
is the essence of CQRS.

## Best practices

**Design for queries.** Structure projections based on how you need to query them, not how your domain is modeled.

**Create multiple projections.** Don't try to create one projection that serves all queries. Build specialized
projections for different use cases.

**Use buffering for consistency.** The buffering mechanism enables batch updates and transactional consistency. Use
middleware to manage commit/rollback automatically.

**Keep projections simple.** Projections should be dumb data structures. Complex logic belongs in the projectors that
update them.

**Keep projectors autonomous.** Projectors should only read and modify the projections they own; avoid reading other
projections. If you need data from multiple projections, create a new combined projection instead.

**Optimize for reads.** Precompute values, denormalize data, and structure projections to make queries fast.

**One projection instance per entity.** Each entity (course, student, etc.) should have its own projection instance with
a unique ID.

**Make projections serializable.** Projections must be serializable for persistence. Use simple types and avoid circular
references.

---
