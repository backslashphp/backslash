---
title: "Defining Services"
weight: 16
bookToc: true
---
# Defining Services

Using a dependency injection container is beneficial for bootstrapping Backslash applications. The number of components
and their dependencies makes a container practical for managing instantiation and wiring.

The demo application implements its own PSR-11 container in `src/Infrastructure/Container.php` for demonstration
purposes. In production applications, prefer using your framework's built-in container (Laravel, Symfony, Slim, etc.) or
a dedicated container library like `php-di/php-di` or `league/container`. These mature containers provide features like
auto-wiring, configuration caching, and better performance.

This section shows how to define Backslash components as services in your dependency injection container.

## Understanding Backslash interfaces

Backslash provides an interface for each component. These interfaces serve as contracts that define what operations each
component supports, independent of their concrete implementations.

The core interfaces include:

- `DispatcherInterface` for command dispatching
- `EventBusInterface` for event publishing and subscription
- `EventStoreInterface` for event persistence and retrieval
- `ProjectionStoreInterface` for projection storage and querying
- `RepositoryInterface` for model loading and persistence
- `SerializerInterface` for object serialization
- `StreamEnricherInterface` for metadata enrichment
- `PdoInterface` for PDO connections

Services depend on interfaces rather than concrete implementations, making code more flexible and testable:

```php
class CourseCommandHandler
{
    public function __construct(
        private RepositoryInterface $repository,  // Depends on interface
    ) {
    }
}
```

This approach allows you to swap implementations without changing dependent code. For testing, you can inject mock
implementations; for production, you inject the real implementations configured in your container.

## Service definition patterns

Most containers support defining services through configuration files, PHP arrays, or using auto-wiring. The examples
below use closure-based definitions compatible with most PSR-11 containers:

```php
// Simple service with no dependencies
StreamEnricherInterface::class => fn () => new StreamEnricher(),

// Service with container-resolved dependencies
RepositoryInterface::class => fn (ContainerInterface $c) => new Repository(
    $c->get(EventStoreInterface::class),
    $c->get(EventBusInterface::class),
),

// Service with configuration
PdoInterface::class => function () {
    $dsn = getenv('DB_DSN') ?: 'sqlite:data/demo.sqlite';
    return new PdoProxy(fn () => new PDO($dsn));
},
```

Adapt these patterns to your container's preferred configuration format. All Backslash components should be registered
as singleton services; instances should be shared and reused throughout your application.

## Configuring the PDO connection

Define the database connection as a service:

```php
use Backslash\Pdo\PdoInterface;
use Backslash\Pdo\PdoProxy;

PdoInterface::class => function () {
    $dsn = getenv('DB_DSN') ?: 'sqlite:data/demo.sqlite';
    $username = getenv('DB_USERNAME') ?: null;
    $password = getenv('DB_PASSWORD') ?: null;
    
    return new PdoProxy(fn () => new PDO($dsn, $username, $password));
},
```

The `PdoProxy` defers the actual PDO connection until the first database operation.

Use environment variables for database configuration to keep credentials out of code and support different environments.

## Configuring the EventStore

Define the EventStore with its adapter and middleware:

```php
use Backslash\EventNameResolver\EventNameResolver;
use Backslash\EventNameResolver\MatchingClassEventNameResolverAdapter;
use Backslash\EventStore\EventStore;
use Backslash\EventStore\EventStoreInterface;
use Backslash\PdoEventStore\PdoEventStoreAdapter;
use Backslash\PdoEventStore\Config as PdoEventStoreConfig;
use Backslash\Serializer\Serializer;
use Backslash\PdoEventStore\JsonEventSerializer;
use Backslash\PdoEventStore\JsonIdentifiersSerializer;
use Backslash\PdoEventStore\JsonMetadataSerializer;
use Backslash\StreamEnricher\StreamEnricherEventStoreMiddleware;
use Ramsey\Uuid\Uuid;

EventStoreInterface::class => function (ContainerInterface $c) {
    $eventNameResolver = new EventNameResolver(new MatchingClassEventNameResolverAdapter());
    $store = new EventStore(
        new PdoEventStoreAdapter(
            $c->get(PdoInterface::class),
            new PdoEventStoreConfig(),
            $eventNameResolver,
            new Serializer(new JsonEventSerializer($eventNameResolver)),
            new Serializer(new JsonIdentifiersSerializer()),
            new Serializer(new JsonMetadataSerializer()),
            fn () => Uuid::uuid4()->toString(),
        ),
    );
    
    $store->addMiddleware(
        new StreamEnricherEventStoreMiddleware(
            $c->get(StreamEnricherInterface::class)
        )
    );
    
    return $store;
},
```

The EventStore requires an adapter (`PdoEventStoreAdapter`), serializers for events, identifiers, and metadata, and a
function to generate event IDs.

Add the `StreamEnricherEventStoreMiddleware` to inject metadata into events as they're persisted.

## Configuring the ProjectionStore

Define the ProjectionStore with its adapter and middleware:

```php
use Backslash\ProjectionStore\ProjectionStore;
use Backslash\ProjectionStore\ProjectionStoreInterface;
use Backslash\PdoProjectionStore\PdoProjectionStoreAdapter;
use Backslash\PdoProjectionStore\Config as PdoProjectionStoreConfig;
use Backslash\Serializer\SerializeFunctionSerializer;
use Backslash\CacheProjectionStoreMiddleware\CacheProjectionStoreMiddleware;

ProjectionStoreInterface::class => function (ContainerInterface $c) {
    $store = new ProjectionStore(
        new PdoProjectionStoreAdapter(
            $c->get(PdoInterface::class),
            new Serializer(new SerializeFunctionSerializer()),
            new PdoProjectionStoreConfig(),
        ),
    );
    
    $store->addMiddleware(new CacheProjectionStoreMiddleware());
    
    return $store;
},
```

The ProjectionStore requires an adapter (`PdoProjectionStoreAdapter`), a serializer, and configuration.

Add the `CacheProjectionStoreMiddleware` to cache projections in memory and reduce database queries.

For testing purposes, you can easily swap the adapter for an in-memory implementation:

```php
use Backslash\InMemoryProjectionStore\InMemoryProjectionStoreAdapter;

ProjectionStoreInterface::class => function (ContainerInterface $c) {
    $isTestMode = (bool) getenv('TESTING');
    
    $adapter = $isTestMode
        ? new InMemoryProjectionStoreAdapter()
        : new PdoProjectionStoreAdapter(
            $c->get(PdoInterface::class),
            new Serializer(new SerializeFunctionSerializer()),
            new PdoProjectionStoreConfig(),
        );
    
    $store = new ProjectionStore($adapter);
    $store->addMiddleware(new CacheProjectionStoreMiddleware());
    
    return $store;
},
```

This approach allows tests to run faster without database dependencies while using the same service definition.

## Configuring the Repository

Define the Repository with its dependencies:

```php
use Backslash\Repository\Repository;
use Backslash\Repository\RepositoryInterface;

RepositoryInterface::class => fn (ContainerInterface $c) => new Repository(
    $c->get(EventStoreInterface::class),
    $c->get(EventBusInterface::class),
),
```

The Repository requires the EventStore for loading and persisting events, and the EventBus for publishing events after
persistence.

## Configuring the EventBus

Define the EventBus with its middleware:

```php
use Backslash\EventBus\EventBus;
use Backslash\EventBus\EventBusInterface;
use Backslash\StreamEnricher\StreamEnricherEventBusMiddleware;

EventBusInterface::class => function (ContainerInterface $c) {
    $bus = new EventBus();
    
    $bus->addMiddleware(
        new StreamEnricherEventBusMiddleware(
            $c->get(StreamEnricherInterface::class)
        )
    );
    
    return $bus;
},
```

Add the `StreamEnricherEventBusMiddleware` to inject metadata into events as they're published, ensuring consistency
with persisted events.

Event handler registration (subscribing event handlers to events) does not happen here; it occurs during the boot
process explained in [section 17](#17-registering-handlers).

## Configuring the Dispatcher

Define the Dispatcher with its middleware:

```php
use Backslash\CommandDispatcher\Dispatcher;
use Backslash\CommandDispatcher\DispatcherInterface;
use Backslash\PdoTransactionCommandDispatcherMiddleware\PdoTransactionCommandDispatcherMiddleware;
use Backslash\ProjectionStoreTransactionCommandDispatcherMiddleware\ProjectionStoreTransactionCommandDispatcherMiddleware;

DispatcherInterface::class => function (ContainerInterface $c) {
    $dispatcher = new Dispatcher();
    
    $dispatcher->addMiddleware(
        new PdoTransactionCommandDispatcherMiddleware(
            $c->get(PdoInterface::class)
        )
    );
    
    $dispatcher->addMiddleware(
        new ProjectionStoreTransactionCommandDispatcherMiddleware(
            $c->get(ProjectionStoreInterface::class)
        )
    );
    
    return $dispatcher;
},
```

Add the `PdoTransactionCommandDispatcherMiddleware` to wrap command execution in a database transaction.

Add the `ProjectionStoreTransactionCommandDispatcherMiddleware` to automatically commit projections on success and
rollback on failure.

Command handler registration does not happen here; it occurs during the boot process explained
in [section 17](#17-registering-handlers).

## Configuring the StreamEnricher

Define the StreamEnricher implementation:

```php
use Backslash\StreamEnricher\StreamEnricherInterface;

StreamEnricherInterface::class => fn () => new AppStreamEnricher(),
```

If your enricher requires dependencies like authentication services or tenant management, inject them through the
constructor:

```php
StreamEnricherInterface::class => fn (ContainerInterface $c) => new AppStreamEnricher(
    $c->get(AuthenticationService::class),
    $c->get(TenantContext::class),
),
```

## Configuring handlers and projectors

Define each command handler, event handler, and projector as services:

```php
// Command Handlers
CourseCommandHandler::class => fn (ContainerInterface $c) => 
    new CourseCommandHandler(
        $c->get(RepositoryInterface::class),
    ),

StudentCommandHandler::class => fn (ContainerInterface $c) => 
    new StudentCommandHandler(
        $c->get(RepositoryInterface::class),
    ),

SubscriptionCommandHandler::class => fn (ContainerInterface $c) => 
    new SubscriptionCommandHandler(
        $c->get(RepositoryInterface::class),
    ),

// Projectors
CourseListProjector::class => fn (ContainerInterface $c) => 
    new CourseListProjector(
        $c->get(ProjectionStoreInterface::class),
    ),

StudentListProjector::class => fn (ContainerInterface $c) => 
    new StudentListProjector(
        $c->get(ProjectionStoreInterface::class),
    ),

// Processors
NotificationProcessor::class => fn (ContainerInterface $c) =>
    new NotificationProcessor(
        $c->get(MailerInterface::class),
    ),
```

Each handler definition injects its required dependencies through the constructor.

---
