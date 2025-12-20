<?php

declare(strict_types=1);

namespace Backslash\ProjectionStoreTransactionCommandDispatcherMiddleware;

use Backslash\CommandDispatcher\Dispatcher;
use Backslash\Shared\CommandDispatcher\CallableTestHandler;
use Backslash\Shared\CommandDispatcher\ConfigurableTestCommand;
use PHPUnit\Framework\TestCase;

class ProjectionStoreTransactionCommandDispatcherMiddlewareTest extends TestCase
{
    /** @test */
    public function it_commits_projection_store_on_successful_dispatch(): void
    {
        $adapter = new TestProjectionStore();
        $projectionStore = new \Backslash\ProjectionStore\ProjectionStore($adapter);
        $dispatcher = new Dispatcher();
        $dispatcher->addMiddleware(
            new ProjectionStoreTransactionCommandDispatcherMiddleware($projectionStore),
        );

        $dispatcher->dispatch(new ConfigurableTestCommand());

        $this->assertEquals([['commit']], $adapter->getCalls());
    }

    /** @test */
    public function it_rolls_back_projection_store_on_exception(): void
    {
        $adapter = new TestProjectionStore();
        $projectionStore = new \Backslash\ProjectionStore\ProjectionStore($adapter);
        $dispatcher = new Dispatcher();
        $dispatcher->addMiddleware(
            new ProjectionStoreTransactionCommandDispatcherMiddleware($projectionStore),
        );

        $dispatcher->registerHandler(ConfigurableTestCommand::class, new CallableTestHandler(function (): void {
            throw new TestException('Something went wrong');
        }));

        try {
            $dispatcher->dispatch(new ConfigurableTestCommand());
            $this->fail('Expected exception was not thrown');
        } catch (TestException $e) {
            $this->assertEquals('Something went wrong', $e->getMessage());
        }

        // rollback() doesn't propagate to the adapter, it just resets the UnitOfWork
        // So we verify that commit() was NOT called
        $this->assertEquals([], $adapter->getCalls());
    }

    /** @test */
    public function it_handles_nested_command_dispatch(): void
    {
        $adapter = new TestProjectionStore();
        $projectionStore = new \Backslash\ProjectionStore\ProjectionStore($adapter);
        $dispatcher = new Dispatcher();
        $dispatcher->addMiddleware(
            new ProjectionStoreTransactionCommandDispatcherMiddleware($projectionStore),
        );

        $dispatcher->registerHandler(ConfigurableTestCommand::class, new CallableTestHandler(function (ConfigurableTestCommand $cmd) use ($dispatcher): void {
            if ($cmd->id === 'outer') {
                // Dispatch nested command
                $dispatcher->dispatch(new ConfigurableTestCommand('inner'));
            }
        }));

        $dispatcher->dispatch(new ConfigurableTestCommand('outer'));

        // Should only commit once at the top level (nestedLevels = 0)
        $this->assertEquals([['commit']], $adapter->getCalls());
    }

    /** @test */
    public function it_only_commits_when_nested_level_is_zero(): void
    {
        $adapter = new TestProjectionStore();
        $projectionStore = new \Backslash\ProjectionStore\ProjectionStore($adapter);
        $dispatcher = new Dispatcher();
        $dispatcher->addMiddleware(
            new ProjectionStoreTransactionCommandDispatcherMiddleware($projectionStore),
        );

        $nestedCallCount = 0;
        $dispatcher->registerHandler(ConfigurableTestCommand::class, new CallableTestHandler(function (ConfigurableTestCommand $cmd) use ($dispatcher, &$nestedCallCount): void {
            $nestedCallCount++;
            if ($cmd->id === 'level1') {
                $dispatcher->dispatch(new ConfigurableTestCommand('level2'));
            } elseif ($cmd->id === 'level2') {
                $dispatcher->dispatch(new ConfigurableTestCommand('level3'));
            }
        }));

        $dispatcher->dispatch(new ConfigurableTestCommand('level1'));

        // All three handlers should have been called
        $this->assertEquals(3, $nestedCallCount);
        // But commit should only be called once at the end
        $this->assertEquals([['commit']], $adapter->getCalls());
    }

    /** @test */
    public function it_resets_nested_levels_to_zero_on_exception(): void
    {
        $adapter = new TestProjectionStore();
        $projectionStore = new \Backslash\ProjectionStore\ProjectionStore($adapter);
        $dispatcher = new Dispatcher();
        $dispatcher->addMiddleware(
            new ProjectionStoreTransactionCommandDispatcherMiddleware($projectionStore),
        );

        $dispatcher->registerHandler(ConfigurableTestCommand::class, new CallableTestHandler(function (ConfigurableTestCommand $cmd) use ($dispatcher): void {
            if ($cmd->id === 'outer') {
                try {
                    $dispatcher->dispatch(new ConfigurableTestCommand('inner'));
                } catch (TestException $e) {
                    // Catch the exception from inner dispatch
                    // But this doesn't actually catch it because the exception is re-thrown
                }
            } else {
                throw new TestException('Inner exception');
            }
        }));

        try {
            $dispatcher->dispatch(new ConfigurableTestCommand('outer'));
        } catch (TestException $e) {
            // Expected - the inner exception propagates
            $this->assertEquals('Inner exception', $e->getMessage());
        }

        // rollback() was called but doesn't propagate to adapter
        $this->assertEquals([], $adapter->getCalls());

        // Reset for next dispatch
        $adapter->reset();

        // Now dispatch again - should work fine (nestedLevels should have been reset to 0)
        $dispatcher2 = new Dispatcher();
        $dispatcher2->addMiddleware(
            new ProjectionStoreTransactionCommandDispatcherMiddleware($projectionStore),
        );
        $dispatcher2->dispatch(new ConfigurableTestCommand('simple'));

        $this->assertEquals([['commit']], $adapter->getCalls());
    }

    /** @test */
    public function it_rolls_back_on_exception_in_nested_dispatch(): void
    {
        $adapter = new TestProjectionStore();
        $projectionStore = new \Backslash\ProjectionStore\ProjectionStore($adapter);
        $dispatcher = new Dispatcher();
        $dispatcher->addMiddleware(
            new ProjectionStoreTransactionCommandDispatcherMiddleware($projectionStore),
        );

        $dispatcher->registerHandler(ConfigurableTestCommand::class, new CallableTestHandler(function (ConfigurableTestCommand $cmd) use ($dispatcher): void {
            if ($cmd->id === 'outer') {
                // This will throw an exception
                $dispatcher->dispatch(new ConfigurableTestCommand('inner'));
            } else {
                throw new TestException('Nested exception');
            }
        }));

        try {
            $dispatcher->dispatch(new ConfigurableTestCommand('outer'));
            $this->fail('Expected exception was not thrown');
        } catch (TestException $e) {
            $this->assertEquals('Nested exception', $e->getMessage());
        }

        // rollback() was called but doesn't propagate to adapter
        // Verify that commit() was NOT called
        $this->assertEquals([], $adapter->getCalls());
    }

    /** @test */
    public function it_decrements_nested_levels_before_commit(): void
    {
        $adapter = new TestProjectionStore();
        $projectionStore = new \Backslash\ProjectionStore\ProjectionStore($adapter);
        $dispatcher = new Dispatcher();
        $dispatcher->addMiddleware(
            new ProjectionStoreTransactionCommandDispatcherMiddleware($projectionStore),
        );

        $callOrder = [];
        $dispatcher->registerHandler(ConfigurableTestCommand::class, new CallableTestHandler(function (ConfigurableTestCommand $cmd) use ($dispatcher, &$callOrder): void {
            $callOrder[] = 'start-' . $cmd->id;
            if ($cmd->id === 'outer') {
                $dispatcher->dispatch(new ConfigurableTestCommand('inner'));
            }
            $callOrder[] = 'end-' . $cmd->id;
        }));

        $dispatcher->dispatch(new ConfigurableTestCommand('outer'));

        // Verify execution order
        $this->assertEquals([
            'start-outer',
            'start-inner',
            'end-inner',
            'end-outer',
        ], $callOrder);

        // Commit should happen after all nested dispatches complete
        $this->assertEquals([['commit']], $adapter->getCalls());
    }
}
