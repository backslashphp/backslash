---
title: "Registering Handlers"
weight: 2
---

The boot process registers commands with the dispatcher and events with the event bus, establishing the routing between
messages and their handlers. This registration happens after service definitions are loaded but before the application
handles any requests or executes commands.

## Understanding the boot process

The boot process consists of:

1. **Registering commands** with command handlers in the dispatcher
2. **Registering events** with projectors in the event bus
3. **Registering events** with processors in the event bus (skipped during projection rebuilds)

This registration should happen once during application startup.

Command and event handler mappings are defined statically (typically as class constants) and used during the boot
process to perform the actual registration.

## Defining command handler mappings

Define command handler mappings using an associative array:

```php
private const COMMAND_HANDLERS = [
    CourseCommandHandler::class => [
        ChangeCourseCapacityCommand::class,
        DefineCourseCommand::class,
    ],
    StudentCommandHandler::class => [
        RegisterStudentCommand::class,
    ],
    SubscriptionCommandHandler::class => [
        SubscribeStudentToCourseCommand::class,
        UnsubscribeStudentFromCourseCommand::class,
    ],
];
```

Each command handler class maps to an array of command classes it handles.

Alternatively, implement a static `getHandledCommands()` method on each command handler to make the mapping explicit:

```php
class CourseCommandHandler
{
    public static function getHandledCommands(): array
    {
        return [
            ChangeCourseCapacityCommand::class,
            DefineCourseCommand::class,
        ];
    }
    
    // Handler methods...
}
```

This approach makes each handler explicitly declare what it handles, eliminating the need for a centralized mapping
array.

## Defining event handler mappings

Define event handler mappings, separating projectors from processors:

```php
private const PROJECTORS = [
    CourseListProjector::class => [
        CourseDefinedEvent::class,
        CourseCapacityChangedEvent::class,
        StudentSubscribedToCourseEvent::class,
        StudentUnsubscribedFromCourseEvent::class,
    ],
    StudentListProjector::class => [
        CourseDefinedEvent::class,
        StudentRegisteredEvent::class,
        StudentSubscribedToCourseEvent::class,
        StudentUnsubscribedFromCourseEvent::class,
    ],
];

private const PROCESSORS = [
    NotificationProcessor::class => [
        StudentSubscribedToCourseEvent::class,
        StudentUnsubscribedFromCourseEvent::class,
    ],
    EmailProcessor::class => [
        StudentRegisteredEvent::class,
    ],
];
```

Maintain separate lists for projectors and processors to enable selective registration.

Alternatively, implement a static `getSubscribedEvents()` method on each event handler to make the mapping explicit:

```php
class CourseListProjector
{
    public static function getSubscribedEvents(): array
    {
        return [
            CourseDefinedEvent::class,
            CourseCapacityChangedEvent::class,
            StudentSubscribedToCourseEvent::class,
            StudentUnsubscribedFromCourseEvent::class,
        ];
    }
    
    // Handler methods...
}
```

This approach makes each handler explicitly declare what events it subscribes to, eliminating the need for centralized
mapping arrays.

## Using handler discovery

When using the static method approach (`getHandledCommands()` and `getSubscribedEvents()`), the boot process can
discover and register handlers automatically:

```php
// List of all command handler classes
$commandHandlerClasses = [
    CourseCommandHandler::class,
    StudentCommandHandler::class,
    SubscriptionCommandHandler::class,
];

// Register command handlers using discovery
foreach ($commandHandlerClasses as $handlerClass) {
    $commands = $handlerClass::getHandledCommands();
    
    foreach ($commands as $commandClass) {
        $container->get(DispatcherInterface::class)->registerHandler(
            $commandClass,
            new HandlerProxy(fn () => $container->get($handlerClass))
        );
    }
}

// List of all event handler classes
$projectorClasses = [
    CourseListProjector::class,
    StudentListProjector::class,
];

$processorClasses = [
    NotificationProcessor::class,
    EmailProcessor::class,
];

// Register projectors using discovery
foreach ($projectorClasses as $projectorClass) {
    $events = $projectorClass::getSubscribedEvents();
    
    foreach ($events as $eventClass) {
        $container->get(EventBusInterface::class)->subscribe(
            $eventClass,
            new EventHandlerProxy(fn () => $container->get($projectorClass))
        );
    }
}

// Register processors (skip during projection rebuilds)
$isRebuildMode = (bool) getenv('REBUILD_PROJECTIONS');

if (!$isRebuildMode) {
    foreach ($processorClasses as $processorClass) {
        $events = $processorClass::getSubscribedEvents();
        
        foreach ($events as $eventClass) {
            $container->get(EventBusInterface::class)->subscribe(
                $eventClass,
                new EventHandlerProxy(fn () => $container->get($processorClass))
            );
        }
    }
}
```

This discovery approach requires maintaining only a list of handler classes rather than complete mappings, reducing
duplication since each handler already declares what it handles.

## Using HandlerProxy and EventHandlerProxy

Backslash provides proxy classes that defer handler instantiation:

**HandlerProxy** for command handlers:

```php
use Backslash\CommandDispatcher\HandlerProxy;

$dispatcher->registerHandler(
    DefineCourseCommand::class,
    new HandlerProxy(fn () => $container->get(CourseCommandHandler::class))
);
```

**EventHandlerProxy** for event handlers:

```php
use Backslash\EventBus\EventHandlerProxy;

$eventBus->subscribe(
    CourseDefinedEvent::class,
    new EventHandlerProxy(fn () => $container->get(CourseListProjector::class))
);
```

These proxies defer handler instantiation until they're actually needed.

## Registering command handlers

The boot process registers command handlers with the dispatcher:

```php
use Backslash\CommandDispatcher\DispatcherInterface;
use Backslash\CommandDispatcher\HandlerProxy;

/** @var DispatcherInterface $dispatcher */
$dispatcher = $container->get(DispatcherInterface::class);

foreach (COMMAND_HANDLERS as $handlerClass => $commandClasses) {
    foreach ($commandClasses as $commandClass) {
        $dispatcher->registerHandler(
            $commandClass,
            new HandlerProxy(fn () => $container->get($handlerClass))
        );
    }
}
```

This registration happens during boot, before dispatching any commands.

## Registering event handlers

The boot process registers event handlers with the event bus:

```php
use Backslash\EventBus\EventBusInterface;
use Backslash\EventBus\EventHandlerProxy;

/** @var EventBusInterface $eventBus */
$eventBus = $container->get(EventBusInterface::class);

// Register projectors
foreach (PROJECTORS as $projectorClass => $eventClasses) {
    foreach ($eventClasses as $eventClass) {
        $eventBus->subscribe(
            $eventClass,
            new EventHandlerProxy(fn () => $container->get($projectorClass))
        );
    }
}

// Register processors (skip during projection rebuilds)
$isRebuildMode = (bool) getenv('REBUILD_PROJECTIONS');

if (!$isRebuildMode) {
    foreach (PROCESSORS as $processorClass => $eventClasses) {
        foreach ($eventClasses as $eventClass) {
            $eventBus->subscribe(
                $eventClass,
                new EventHandlerProxy(fn () => $container->get($processorClass))
            );
        }
    }
}
```

The application determines whether it's in rebuild mode (typically using an environment variable like
`REBUILD_PROJECTIONS`). During projection rebuilds, processors are not registered to avoid triggering side effects like
sending duplicate notifications or making external API calls.

---
