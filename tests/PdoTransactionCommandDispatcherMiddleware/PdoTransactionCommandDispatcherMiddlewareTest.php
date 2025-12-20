<?php

declare(strict_types=1);

namespace Backslash\PdoTransactionCommandDispatcherMiddleware;

use Backslash\CommandDispatcher\Dispatcher;
use Backslash\Shared\CommandDispatcher\CallableTestHandler;
use Backslash\Shared\CommandDispatcher\ConfigurableTestCommand;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PdoTransactionCommandDispatcherMiddlewareTest extends TestCase
{
    /** @test */
    public function it_begins_and_commits_transaction_on_successful_dispatch(): void
    {
        $pdo = new TestPdo();
        $dispatcher = new Dispatcher();
        $dispatcher->addMiddleware(new PdoTransactionCommandDispatcherMiddleware($pdo));

        $dispatcher->dispatch(new ConfigurableTestCommand());

        $this->assertEquals(['beginTransaction', 'commit'], $pdo->getCalls());
        $this->assertFalse($pdo->inTransaction());
    }

    /** @test */
    public function it_begins_and_rolls_back_transaction_on_exception(): void
    {
        $pdo = new TestPdo();
        $dispatcher = new Dispatcher();
        $dispatcher->addMiddleware(new PdoTransactionCommandDispatcherMiddleware($pdo));

        $dispatcher->registerHandler(ConfigurableTestCommand::class, new CallableTestHandler(function (): void {
            throw new TestException('Something went wrong');
        }));

        try {
            $dispatcher->dispatch(new ConfigurableTestCommand());
            $this->fail('Expected exception was not thrown');
        } catch (TestException $e) {
            $this->assertEquals('Something went wrong', $e->getMessage());
        }

        $this->assertEquals(['beginTransaction', 'rollBack'], $pdo->getCalls());
        $this->assertFalse($pdo->inTransaction());
    }

    /** @test */
    public function it_handles_nested_command_dispatch(): void
    {
        $pdo = new TestPdo();
        $dispatcher = new Dispatcher();
        $dispatcher->addMiddleware(new PdoTransactionCommandDispatcherMiddleware($pdo));

        $dispatcher->registerHandler(ConfigurableTestCommand::class, new CallableTestHandler(function (ConfigurableTestCommand $cmd) use ($dispatcher): void {
            if ($cmd->id === 'outer') {
                // Dispatch nested command
                $dispatcher->dispatch(new ConfigurableTestCommand('inner'));
            }
        }));

        $dispatcher->dispatch(new ConfigurableTestCommand('outer'));

        // Should only begin/commit once at the top level
        $this->assertEquals(['beginTransaction', 'commit'], $pdo->getCalls());
        $this->assertFalse($pdo->inTransaction());
    }

    /** @test */
    public function it_defers_safe_exceptions_until_transaction_commit(): void
    {
        $pdo = new TestPdo();
        $dispatcher = new Dispatcher();
        $dispatcher->addMiddleware(
            new PdoTransactionCommandDispatcherMiddleware($pdo, TestSafeException::class),
        );

        $dispatcher->registerHandler(ConfigurableTestCommand::class, new CallableTestHandler(function (): void {
            throw new TestSafeException('Safe exception');
        }));

        try {
            $dispatcher->dispatch(new ConfigurableTestCommand());
            $this->fail('Expected safe exception was not thrown');
        } catch (TestSafeException $e) {
            $this->assertEquals('Safe exception', $e->getMessage());
        }

        // Transaction should commit, THEN throw the exception
        $this->assertEquals(['beginTransaction', 'commit'], $pdo->getCalls());
        $this->assertFalse($pdo->inTransaction());
    }

    /** @test */
    public function it_defers_multiple_safe_exceptions_in_nested_dispatches(): void
    {
        $pdo = new TestPdo();
        $dispatcher = new Dispatcher();
        $dispatcher->addMiddleware(
            new PdoTransactionCommandDispatcherMiddleware($pdo, TestSafeException::class),
        );

        $dispatcher->registerHandler(ConfigurableTestCommand::class, new CallableTestHandler(function (ConfigurableTestCommand $cmd) use ($dispatcher): void {
            if ($cmd->id === 'outer') {
                // Dispatch nested command that throws safe exception
                try {
                    $dispatcher->dispatch(new ConfigurableTestCommand('inner'));
                } catch (TestSafeException $e) {
                    // This won't catch because it's deferred
                }
                // Also throw a safe exception in outer
                throw new TestSafeException('Outer exception');
            } else {
                throw new TestSafeException('Inner exception');
            }
        }));

        try {
            $dispatcher->dispatch(new ConfigurableTestCommand('outer'));
            $this->fail('Expected exception was not thrown');
        } catch (TestSafeException $e) {
            // Should get the first deferred exception
            $this->assertEquals('Inner exception', $e->getMessage());
        }

        // Transaction should commit
        $this->assertEquals(['beginTransaction', 'commit'], $pdo->getCalls());
    }

    /** @test */
    public function it_rolls_back_on_unsafe_exception_even_with_deferred_safe_exceptions(): void
    {
        $pdo = new TestPdo();
        $dispatcher = new Dispatcher();
        $dispatcher->addMiddleware(
            new PdoTransactionCommandDispatcherMiddleware($pdo, TestSafeException::class),
        );

        $dispatcher->registerHandler(ConfigurableTestCommand::class, new CallableTestHandler(function (ConfigurableTestCommand $cmd) use ($dispatcher): void {
            if ($cmd->id === 'outer') {
                try {
                    $dispatcher->dispatch(new ConfigurableTestCommand('inner'));
                } catch (TestSafeException $e) {
                    // Won't catch
                }
                // Throw unsafe exception
                throw new TestException('Unsafe exception');
            } else {
                throw new TestSafeException('Safe exception');
            }
        }));

        try {
            $dispatcher->dispatch(new ConfigurableTestCommand('outer'));
            $this->fail('Expected exception was not thrown');
        } catch (TestException $e) {
            $this->assertEquals('Unsafe exception', $e->getMessage());
        }

        // Should rollback, not commit
        $this->assertEquals(['beginTransaction', 'rollBack'], $pdo->getCalls());
    }

    /** @test */
    public function it_throws_exception_when_transaction_already_started(): void
    {
        $pdo = new TestPdo();
        $pdo->setInTransaction(true);

        $dispatcher = new Dispatcher();
        $dispatcher->addMiddleware(new PdoTransactionCommandDispatcherMiddleware($pdo));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Transaction already started.');

        $dispatcher->dispatch(new ConfigurableTestCommand());
    }

    /** @test */
    public function it_throws_exception_when_not_in_transaction_on_commit(): void
    {
        $pdo = new TestPdo();
        $dispatcher = new Dispatcher();
        $middleware = new PdoTransactionCommandDispatcherMiddleware($pdo);
        $dispatcher->addMiddleware($middleware);

        $dispatcher->registerHandler(ConfigurableTestCommand::class, new CallableTestHandler(function () use ($pdo): void {
            // Manually commit the transaction during dispatch
            $pdo->commit();
        }));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not in transaction.');

        $dispatcher->dispatch(new ConfigurableTestCommand());
    }

    /** @test */
    public function it_does_not_rollback_when_not_in_transaction(): void
    {
        $pdo = new TestPdo();
        $dispatcher = new Dispatcher();
        $dispatcher->addMiddleware(new PdoTransactionCommandDispatcherMiddleware($pdo));

        $dispatcher->registerHandler(ConfigurableTestCommand::class, new CallableTestHandler(function () use ($pdo): void {
            // Manually rollback during dispatch
            $pdo->rollBack();
            throw new TestException('Error after manual rollback');
        }));

        try {
            $dispatcher->dispatch(new ConfigurableTestCommand());
            $this->fail('Expected exception was not thrown');
        } catch (TestException $e) {
            // Expected
        }

        // rollbackTransaction() checks inTransaction() before calling rollBack()
        // So after manual rollBack, the middleware won't call rollBack() again
        $this->assertEquals(['beginTransaction', 'rollBack'], $pdo->getCalls());
    }
}
