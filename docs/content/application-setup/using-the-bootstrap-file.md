---
title: "Using the Bootstrap File"
weight: 18
bookToc: true
---
# Using the Bootstrap File

The bootstrap file provides a single entry point for configuring your container and booting your application. This file
can be included in CLI scripts, web applications, and tests to ensure consistent initialization.

## Creating the bootstrap file

Create a `bootstrap.php` file at your project root:

```php
<?php

declare(strict_types=1);

namespace MyApp;

use Backslash\CommandDispatcher\DispatcherInterface;
use Backslash\CommandDispatcher\HandlerProxy;
use Backslash\EventBus\EventBusInterface;
use Backslash\EventBus\EventHandlerProxy;
use Psr\Container\ContainerInterface;

return (function (): ContainerInterface {
    const COMMAND_HANDLERS = [
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

    const PROJECTORS = [
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

    const PROCESSORS = [
        NotificationProcessor::class => [
            StudentSubscribedToCourseEvent::class,
            StudentUnsubscribedFromCourseEvent::class,
        ],
        EmailProcessor::class => [
            StudentRegisteredEvent::class,
        ],
    ];

    chdir(__DIR__);
    require 'vendor/autoload.php';

    // Load application configuration
    $config = include 'config.php';

    // Get your framework's container or instantiate your own implementation
    /** @var ContainerInterface $container */
    $container = new MyAppContainer($config);

    // Boot process: register command handlers
    foreach (COMMAND_HANDLERS as $handlerClass => $commandClasses) {
        foreach ($commandClasses as $commandClass) {
            $container->get(DispatcherInterface::class)->registerHandler(
                $commandClass,
                new HandlerProxy(fn () => $container->get($handlerClass))
            );
        }
    }

    // Boot process: register event handlers
    foreach (PROJECTORS as $projectorClass => $eventClasses) {
        foreach ($eventClasses as $eventClass) {
            $container->get(EventBusInterface::class)->subscribe(
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
                $container->get(EventBusInterface::class)->subscribe(
                    $eventClass,
                    new EventHandlerProxy(fn () => $container->get($processorClass))
                );
            }
        }
    }

    return $container;
})();
```

This file loads the autoloader, retrieves the configured container, executes the boot process, and returns the
ready-to-use container.

## Using in CLI scripts

Include the bootstrap in command-line scripts:

```php
<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Backslash\CommandDispatcher\DispatcherInterface;
use Demo\Application\Command\Course\DefineCourseCommand;

/** @var ContainerInterface $container */
$container = require __DIR__ . '/../bootstrap.php';

/** @var DispatcherInterface $dispatcher */
$dispatcher = $container->get(DispatcherInterface::class);

// Execute commands
$dispatcher->dispatch(new DefineCourseCommand('101', 'Introduction to PHP', 30));
$dispatcher->dispatch(new DefineCourseCommand('102', 'Advanced PHP', 20));

echo "Courses created successfully\n";
```

This pattern makes all components available for administrative tasks, data imports, or cron jobs.

## Using in web applications

Include the bootstrap in your web entry point (e.g., `public/index.php`).

Here's an example using Slim Framework:

```php
<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Slim\Factory\AppFactory;

/** @var ContainerInterface $container */
$container = require __DIR__ . '/../bootstrap.php';

// Set container to create App with on AppFactory
AppFactory::setContainer($container);
$app = AppFactory::create();

// Define routes
$app->get('/students/{id}', function ($request, $response) use ($container) {
    $projectionStore = $container->get(ProjectionStoreInterface::class);
    $student = $projectionStore->find(
        $request->getAttribute('id'),
        StudentProjection::class
    );
    
    $response->getBody()->write(json_encode($student));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/courses', function ($request, $response) use ($container) {
    $data = $request->getParsedBody();
    
    $dispatcher = $container->get(DispatcherInterface::class);
    $dispatcher->dispatch(
        new DefineCourseCommand($data['id'], $data['name'], $data['capacity'])
    );
    
    return $response->withStatus(201);
});

// Run application
$app->run();
```

The bootstrap provides all services needed to handle HTTP requests.

## Using in tests

Include the bootstrap in your test base class:

```php
<?php

namespace Tests;

use Backslash\CommandDispatcher\DispatcherInterface;
use Backslash\EventBus\EventBusInterface;
use Backslash\EventStore\EventStoreInterface;
use Backslash\ProjectionStore\ProjectionStoreInterface;
use Backslash\Scenario\Scenario;
use Backslash\Scenario\AssertionsTrait;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Psr\Container\ContainerInterface;

abstract class TestCase extends PHPUnitTestCase
{
    use AssertionsTrait;

    protected Scenario $scenario;
    private ContainerInterface $container;

    public function setUp(): void
    {
        parent::setUp();

        /** @var ContainerInterface $container */
        $this->container = require __DIR__ . '/../bootstrap.php';

        $this->scenario = new Scenario(
            $this->container->get(EventBusInterface::class),
            $this->container->get(DispatcherInterface::class),
            $this->container->get(ProjectionStoreInterface::class),
            $this->container->get(EventStoreInterface::class),
        );
    }
}
```

Tests use the same bootstrap as production, ensuring consistency.

## Best practices

**Use your framework's container.** If your application uses a framework like Laravel or Symfony, use its built-in
container rather than implementing your own.

**Use established container libraries.** For framework-agnostic applications, use mature containers like `php-di/php-di`
or `league/container` that provide auto-wiring and performance optimizations.

**Keep bootstrap centralized.** All component configuration and boot logic should be accessible from the bootstrap file.

**Use environment variables.** Keep environment-specific settings like database credentials and API keys out of code.

**Use the same bootstrap everywhere.** CLI scripts, web entry points, and tests should all use the same bootstrap file
to ensure consistency.

**Execute boot once.** The boot process (handler registration) should happen once during application initialization, not
on every request.


---
