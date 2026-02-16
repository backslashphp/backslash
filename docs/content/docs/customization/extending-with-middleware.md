---
title: "Extending with Middleware"
weight: 1
---

Middleware provides a powerful way to add cross-cutting concerns to Backslash components. It follows an onion layer
model, allowing logic to be executed before and after core operations.

## Understanding middleware

Middleware wraps component operations in layers. Each middleware can:

- Execute logic before delegating to the next layer
- Execute logic after the next layer completes
- Transform inputs or outputs
- Handle errors or side effects

The onion layer model ensures symmetric execution:

```
Outer Layer → Middle Layer → Inner Layer → [Core] → Inner Layer → Middle Layer → Outer Layer
```

Multiple Backslash core components support middleware extension:

- **Dispatcher**
- **EventBus**
- **EventNameResolver**
- **EventStore**
- **ProjectionStore**
- **Repository**
- **Serializer** (dependency of ProjectionStore and EventStore for serializing projections and events to text)

## CommandDispatcher middleware

Dispatcher middleware wraps command dispatch. Common use cases include logging commands or handling errors:

```php
use Backslash\CommandDispatcher\DispatcherInterface;
use Backslash\CommandDispatcher\MiddlewareInterface;

class LoggingCommandDispatcherMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function dispatch(object $command, DispatcherInterface $next): void
    {
        $this->logger->info('Dispatching command', [
            'command' => $command::class,
        ]);

        try {
            $next->dispatch($command);
            $this->logger->info('Command completed successfully');
        } catch (Throwable $e) {
            $this->logger->error('Command failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
```

Register it with the dispatcher:

```php
$dispatcher->addMiddleware(new LoggingCommandDispatcherMiddleware($logger));
```

## EventStore middleware

EventStore middleware wraps access to the storage adapter.

```php
use Backslash\EventStore\MiddlewareInterface;
use Backslash\EventStore\EventStoreInterface;
use Backslash\Event\RecordedEventStream;
use Backslash\EventStore\Query\QueryInterface;

class StreamEnricherEventStoreMiddleware implements MiddlewareInterface
{
    public function __construct(
        private StreamEnricherInterface $enricher,
    ) {
    }

    public function append(
        RecordedEventStream $stream,
        ?QueryInterface $concurrencyCheck,
        ?int $expectedSequence,
        EventStoreInterface $next
    ): void {
        $enrichedStream = $this->enricher->enrich($stream);
        $next->append($enrichedStream, $concurrencyCheck, $expectedSequence);
    }

    public function fetch(
        ?QueryInterface $query,
        int $fromSequence,
        EventStoreInterface $next
    ): StoredRecordedEventStream {
        return $next->fetch($query, $fromSequence);
    }

    public function inspect(InspectorInterface $inspector, EventStoreInterface $next): void
    {
        $next->inspect($inspector);
    }

    public function purge(EventStoreInterface $next): void
    {
        $next->purge();
    }
}
```

Register it with the EventStore:

```php
$eventStore->addMiddleware(new StreamEnricherEventStoreMiddleware($enricher));
```

## EventBus middleware

EventBus middleware wraps event publishing. Common use cases include enriching events before broadcasting them:

```php
use Backslash\EventBus\MiddlewareInterface;
use Backslash\EventBus\EventBusInterface;
use Backslash\Event\RecordedEventStream;

class StreamEnricherEventBusMiddleware implements MiddlewareInterface
{
    public function __construct(
        private StreamEnricherInterface $enricher,
    ) {
    }

    public function publish(
        RecordedEventStream $stream,
        EventBusInterface $next
    ): void {
        $enrichedStream = $this->enricher->enrich($stream);
        $next->publish($enrichedStream);
    }
}
```

Register it with the EventBus:

```php
$eventBus->addMiddleware(new StreamEnricherEventBusMiddleware($enricher));
```

## ProjectionStore middleware

ProjectionStore middleware wraps access to the storage adapter.

```php
use Backslash\ProjectionStore\MiddlewareInterface;
use Backslash\ProjectionStore\ProjectionStoreInterface;
use Backslash\Projection\ProjectionInterface;

class LoggingProjectionStoreMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function find(string $id, string $class, ProjectionStoreInterface $next): ProjectionInterface
    {
        $this->logger->debug('Loading projection', ['id' => $id, 'class' => $class]);
        return $next->find($id, $class);
    }

    public function has(string $id, string $class, ProjectionStoreInterface $next): bool
    {
        return $next->has($id, $class);
    }

    public function store(ProjectionInterface $projection, ProjectionStoreInterface $next): void
    {
        $this->logger->debug('Storing projection', ['class' => $projection::class]);
        $next->store($projection);
    }

    // Implement other required methods...
}
```

## Repository middleware

Repository middleware wraps model loading and changes persistence. Common use cases include modifying queries to apply
additional conditions, such as tenant scoping in multi-tenant applications.

The following example shows a middleware that adds tenant filtering to all queries, avoiding the need to repeat this
condition in every query throughout the application:

```php
use Backslash\Repository\MiddlewareInterface;
use Backslash\Repository\RepositoryInterface;
use Backslash\Model\ModelInterface;
use Backslash\EventStore\Query\QueryInterface;
use Backslash\EventStore\Query\Metadata;

class TenantScopingRepositoryMiddleware implements MiddlewareInterface
{
    public function __construct(
        private string $tenantId,
    ) {
    }

    public function loadModel(
        string $modelClass,
        ?QueryInterface $query,
        RepositoryInterface $next
    ): ModelInterface {
        // Add tenant filtering to query
        $scopedQuery = $query?->and(Metadata::is('tenant_id', $this->tenantId));
        return $next->loadModel($modelClass, $scopedQuery);
    }

    public function storeChanges(ModelInterface $model, RepositoryInterface $next): void
    {
        $next->storeChanges($model);
    }
}
```

## Built-in middleware

Backslash provides several ready-to-use middleware:

**CacheProjectionStoreMiddleware** - Caches loaded projections in memory to avoid unnecessary storage communication,
significantly improving performance when projections are accessed multiple times:

```php
use Backslash\CacheProjectionStoreMiddleware\CacheProjectionStoreMiddleware;

$projectionStore->addMiddleware(new CacheProjectionStoreMiddleware());
```

**PdoTransactionCommandDispatcherMiddleware** - Creates a PDO transaction for command dispatch; rolls back if an
exception occurs to prevent new events from being written to the database:

```php
use Backslash\PdoTransactionCommandDispatcherMiddleware\PdoTransactionCommandDispatcherMiddleware;

$dispatcher->addMiddleware(new PdoTransactionCommandDispatcherMiddleware($pdo));
```

**ProjectionStoreTransactionCommandDispatcherMiddleware** - Calls `commit()` on ProjectionStore after command processing
completes successfully:

```php
use Backslash\ProjectionStoreTransactionCommandDispatcherMiddleware\ProjectionStoreTransactionCommandDispatcherMiddleware;

$dispatcher->addMiddleware(
    new ProjectionStoreTransactionCommandDispatcherMiddleware($projectionStore)
);
```

**StreamEnricherEventBusMiddleware** - Enriches events before publishing to EventBus:

```php
use Backslash\StreamEnricher\StreamEnricherEventBusMiddleware;

$eventBus->addMiddleware(new StreamEnricherEventBusMiddleware($enricher));
```

**StreamEnricherEventStoreMiddleware** - Enriches events before persisting to EventStore:

```php
use Backslash\StreamEnricher\StreamEnricherEventStoreMiddleware;

$eventStore->addMiddleware(new StreamEnricherEventStoreMiddleware($enricher));
```

## Middleware execution order

Middleware executes in reverse order of registration (LIFO - Last In, First Out). The last middleware added executes
first:

```php
$dispatcher->addMiddleware($logging);      // Executes third (innermost)
$dispatcher->addMiddleware($validation);   // Executes second
$dispatcher->addMiddleware($transaction);  // Executes first (outermost)
```

When a command is dispatched:

1. Transaction middleware starts transaction
2. Validation middleware validates command
3. Logging middleware logs the command
4. Command handler executes
5. Logging middleware logs completion
6. Validation middleware completes
7. Transaction middleware commits

This onion-layer pattern ensures middleware executes symmetrically before and after the core operation.

Here's a concrete example using Backslash's built-in middleware:

```php
use Backslash\ProjectionStoreTransactionCommandDispatcherMiddleware\ProjectionStoreTransactionCommandDispatcherMiddleware;
use Backslash\PdoTransactionCommandDispatcherMiddleware\PdoTransactionCommandDispatcherMiddleware;

// Order matters: PDO transaction wraps everything
$dispatcher->addMiddleware(new LoggingMiddleware($logger));                                            // Innermost
$dispatcher->addMiddleware(new ProjectionStoreTransactionCommandDispatcherMiddleware($projectionStore)); // Middle
$dispatcher->addMiddleware(new PdoTransactionCommandDispatcherMiddleware($pdo));                       // Outermost
```

Execution flow:

1. PDO transaction begins
2. ProjectionStore prepares for changes
3. Logging records command
4. Command executes, events are persisted
5. Logging records completion
6. ProjectionStore commits buffered projections
7. PDO transaction commits

## Inner middleware

By default, `addMiddleware()` adds a new outer layer to the onion. If you need a middleware to execute closest to the
core (innermost layer), use `addInnerMiddleware()` instead:

```php
$dispatcher->addMiddleware($transaction);          // Outer layer
$dispatcher->addMiddleware($logging);              // Middle layer
$dispatcher->addInnerMiddleware($tracing);         // Inner layer (closest to core)
```

Execution flow:

```
Transaction → Logging → Tracing → [Core] → Tracing → Logging → Transaction
```

This is useful when a middleware must always run closest to the core, regardless of what other middleware has already
been registered. For example, the Scenario testing component uses `addInnerMiddleware()` to ensure its tracing middleware
captures exactly what the core produces, even when the host application has added its own middleware to the EventBus or
ProjectionStore.

All components that support middleware provide both `addMiddleware()` and `addInnerMiddleware()`.

## Best practices

**Keep middleware focused.** Each middleware should handle a single concern; avoid creating god middleware that handles
multiple responsibilities.

**Make middleware reusable.** Write middleware that works with any component of its type, not just specific use cases.

**Consider execution order.** Add middleware in the correct order based on dependencies; transactions should wrap
validation, validation should wrap logging, etc.

**Handle exceptions appropriately.** Middleware can catch and transform exceptions, but be careful not to swallow
important errors that should propagate to callers.

**Test middleware independently.** Middleware should be testable in isolation from the components they wrap.

**Document side effects.** If middleware modifies state or has side effects, document this clearly in comments or
documentation.

**Use built-in middleware when available.** Backslash provides common middleware implementations; use them instead of
rolling your own.

---
