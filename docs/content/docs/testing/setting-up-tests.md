---
title: "Setting Up Tests"
weight: 1
---

Testing event-sourced applications requires specific infrastructure setup. This section shows how to configure PHPUnit
and create a base test case for your Backslash application.

## Installing PHPUnit

Add PHPUnit to your project:

```shell
composer require --dev phpunit/phpunit
```

## Creating phpunit.xml

Create a `phpunit.xml` configuration file in your project root:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         verbose="true">
    <testsuites>
        <testsuite name="Application Tests">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <php>
        <env name="TESTING" value="true"/>
        <env name="PDO_DSN" value="sqlite:memory:"/>
    </php>
</phpunit>
```

## Creating a base test case

Create an abstract base test case that sets up the infrastructure components needed for testing. The primary goal is to
instantiate a `Scenario` object, which will be used by test scenarios explained
in [section 13](13-writing-test-scenarios.md).

The way you obtain Backslash components depends heavily on how your application is structured. Most PHP applications
use a dependency injection container. Here's an example using a PSR-11 container:

```php
<?php

declare(strict_types=1);

namespace Tests;

use Backslash\CommandDispatcher\DispatcherInterface;
use Backslash\EventBus\EventBusInterface;
use Backslash\PdoEventStore\Config;
use Backslash\PdoEventStore\Driver;
use Backslash\ProjectionStore\ProjectionStoreInterface;
use Backslash\Scenario\AssertionsTrait;
use Backslash\Scenario\Scenario;
use PDO;
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

        // Bootstrap your DI container
        $this->container = require __DIR__ . '/../bootstrap.php';

        // Get PDO SQLite instance – Make sure your DI container uses the PDO_DSN variable in phpunit.xml when running tests! 
        $pdo = $this->container->get(PDO::class);
        
        // Create table for the EventStore
        $pdo->exec(Driver::SQLITE->buildCreateTableStatement(new Config()));

        // Create Scenario instance with required dependencies
        $this->scenario = new Scenario(
            $this->container->get(EventBusInterface::class),
            $this->container->get(DispatcherInterface::class),
            // Your DI container must use the InMemoryProjectionStoreAdapter when running tests.
            $this->container->get(ProjectionStoreInterface::class),
            $this->container->get(EventStoreInterface::class)
        );

        // Set up initial test data
        $this->createInitialTestData();
    }

    private function createInitialTestData(): void
    {
        $dispatcher = $this->container->get(DispatcherInterface::class);

        // Dispatch commands to generate data used by test scenarios
        // Example: $dispatcher->dispatch(new RegisterUserCommand('user-1', 'John', 'Smith'));
    }
}
```

This base test case provides:

- **Container bootstrap**: Loads the application's dependency injection container
- **Database setup**: Creates EventStore tables using the SQLite driver; `Driver::SQLITE` generates the appropriate
  CREATE TABLE statement, while `Config` allows customization of table and column names if needed
- **In-memory SQLite**: Fast and isolated; each test gets a fresh database without external dependencies
- **Scenario instance**: Ready-to-use `Scenario` with EventBus, Dispatcher, and ProjectionStore
- **AssertionsTrait**: Provides scenario-specific assertions like `assertPublishedEventsContain()`,
  `assertPublishedEventsCount()`, and `assertUpdatedProjectionsContain()`, which will be covered in section 13
- **Initial test data**: Optional `createInitialTestData()` method to set up common fixtures shared across tests; use
  this to create baseline data like system users or default configurations, avoiding duplication in individual tests

---
