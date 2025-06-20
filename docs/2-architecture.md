# 2. Architecture

Backslash is designed around the core principles of event sourcing and CQRS. Its architecture is modular, lightweight,
and framework-agnostic, allowing developers to adopt it gradually or integrate it into existing systems.


## Core components

These components are provided by Backslash and take care of the core mechanics behind event sourcing and the Dynamic
Consistency Boundary.

### Repository

The `Repository` acts as a facade over Baskslash's underlying `EventStore` and `EventBus`. It coordinates the full
lifecycle of `States` by:

- Loading relevant events from the `EventStore` using a `Query`,
- Rebuilding the target `State` by replaying those events,
- Persisting any new events emitted by the `State`,
- Publishing successfully stored events on the `EventBus`.

```php
/** @var RepositoryInterface $repository */
$state = $repository->load(MyState::class, $query);
$state->doSomething();
$repository->store($state);
```

### EventStore

The `EventStore` is an append-only, queryable store that holds all recorded domain events. It plays a central role in
enabling dynamic consistency boundaries.

Backslash includes a built-in PDO-based implementation, `PdoEventStore`, which supports both MySQL and SQLite out of the
box.

### EventBus

The `EventBus` is responsible for **publishing events** to the appropriate event handlers. After events are stored in
the `EventStore`, they are **synchronously dispatched** to all subscribed handlers via the bus.

You can subscribe **multiple handlers to the same event**, and a single handler can handle **multiple event types**.

```php
$eventBus->subscribe(MyEvent::class, new MyEventHandler());
```

#### Lazy loading support

To avoid instantiating all handlers eagerly at startup, Backslash provides `EventHandlerProxy`. This enables **lazy
loading**, meaning handlers are only created when the corresponding event is dispatched:

```php
use Backslash\EventBus\EventHandlerProxy;

$eventBus->subscribe(MyEvent::class, new EventHandlerProxy(fn() => new MyEventHandler()));
```

This approach enhances performance and provides finer control over dependencies — particularly useful when working with
service containers or dependency injection frameworks.

### Dispatcher

The `Dispatcher` directs commands to their corresponding handlers. It acts as the central hub for command processing,
ensuring each command is delegated to the appropriate handler.

```php
use Backslash\CommandDispatcher\Dispatcher;

/* Register a command handler */
/** @var Dispatcher $dispatcher */
$dispatcher->registerHandler(MyCommand::class, new MyCommandHandler());

/* Dispatch a command */
$dispatcher->dispatch(new MyCommand());
```

#### Lazy loading handlers

Like with the `EventBus`, Backslash offers `CommandHandlerProxy` to enable lazy instantiation of command handlers.
Instead of creating all handlers upfront, handlers are only instantiated when their associated command
is dispatched:

```php
use Backslash\CommandDispatcher\Dispatcher;
use Backslash\CommandDispatcher\HandlerProxy;

/** @var Dispatcher $dispatcher */
$dispatcher->registerHandler(
    MyCommand::class,
    new HandlerProxy(fn() => new MyCommandHandler()),
);
```

This pattern is especially useful when integrating with service containers or dependency injection frameworks, giving
you finer control over your application’s dependencies.

### ProjectionStore

The `ProjectionStore` is responsible for persisting **projections**, which are read models used to efficiently query
application state.

Backslash provides `PdoProjectionStore`, a ready-to-use adapter compatible with `PDO`.

If a different storage backend is required, you can implement the `Backslash\ProjectionStore\AdapterInterface` to create
a custom adapter, ensuring full flexibility and seamless integration with Backslash’s projection system.

To retrieve a projection, pass its identifier and fully qualified class name (FQCN) to the `find()` method:

```php
use Backslash\ProjectionStore\ProjectionStoreInterface;

/** @var ProjectionStoreInterface $projectionStore */
$projection = $projectionStore->find($userId, UserProjection::class);
```

### PdoProxy

Backslash provides `PdoProxy` to enable lazy instantiation of `PDO` connections. This helps defer database connection
setup until it’s actually needed, which can be especially useful during application bootstrapping or testing.

```php
use Backslash\Pdo\PdoProxy;

$pdo = new PdoProxy(fn() => new PDO($dsn, $username, $password, $options));
```

`PdoProxy` implements `PdoInterface`, the abstraction expected by adapters such as:

- `PdoEventStore`
- `PdoProjectionStore`

By relying on `PdoInterface`, these components can work with either an eagerly instantiated `PDO` or a lazy `PdoProxy`
transparently.

### Serializer

### StreamEnricher

### Scenario

## Application-level components

These are implemented by the application developer and define the business logic and application behavior.

### States

### Events

### Event handlers

Handlers can be **projectors** (for updating read models) or **processors** (for triggering side effects).

### Projections

Projections must have a unique id and FQCN match.

### Commands

`Commands` are `simple data objects` that represent an intention to perform an action in the system. They carry all the
information needed to execute the requested operation.

Backslash does not require commands to implement any specific interface or base class — you are free to design them as
plain PHP classes or value objects.

```php
readonly class RegisterUserCommand
{
    public function __construct(
        public string $userId,
        public string $name,
    ) {
    }
}
```

### Command handlers

Command handlers encapsulate the logic for executing commands.

### Test scenarios

## Extensibility using middlewares

Backslash core components are designed to be extensible through **before/after middlewares**. Each extensible component
defines its own `MiddlewareInterface`, allowing developers to intercept and customize behavior without modifying core
logic.

| Component           | Middleware interface                              |
|---------------------|---------------------------------------------------|
| `CommandDispatcher` | `Backslash\CommandDispatcher\MiddlewareInterface` |
| `EventBus`          | `Backslash\EventBus\MiddlewareInterface`          |
| `EventStore`        | `Backslash\EventStore\MiddlewareInterface`        |
| `ProjectionStore`   | `Backslash\ProjectionStore\MiddlewareInterface`   |
| `Repository`        | `Backslash\Repository\MiddlewareInterface`        |
| `Serializer`        | `Backslash\Serializer\MiddlewareInterface`        |

Middlewares are executed in sequence and can perform actions before and after the delegated operation. This pattern is
ideal for:

- Logging and observability
- Metrics and performance tracking
- Security checks or authorization
- Correlation ID injection
- Retry logic and error handling

### Example

Here’s an example of a custom middleware for the `CommandDispatcher`:

```php
use Backslash\CommandDispatcher\DispatcherInterface;
use Backslash\CommandDispatcher\MiddlewareInterface;

class MyDispatcherMiddleware implements MiddlewareInterface
{
    public function dispatch(object $command, DispatcherInterface $next): void
    {
        // Logic before dispatching the command
        $next->dispatch($command);
        // Logic after dispatching the command
    }
}
```

Middlewares can be added programmatically or configured as part of your application’s setup process.

```php
$commandDispatcher->addMiddleware(new MyDispatcherMiddleware());
```
