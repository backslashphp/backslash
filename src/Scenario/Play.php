<?php

declare(strict_types=1);

namespace Backslash\Scenario;

use Backslash\Aggregate\Stream;
use Backslash\CommandDispatcher\DispatcherInterface;
use Backslash\EventBus\EventBusInterface;
use Backslash\EventStore\EventStoreInterface;
use Exception;

final class Play
{
    /** @var array<Stream|callable> */
    private array $initialStreams = [];

    /** @var array<object|callable> */
    private array $initialCommands = [];

    /** @var array<object|callable> */
    private array $commands = [];

    /** @var callable[] */
    private array $actions = [];

    /** @var callable[] */
    private array $eventsAssertions = [];

    /** @var callable[] */
    private array $projectionsAssertions = [];

    /** @var callable[] */
    private array $customAssertions = [];

    private ?string $expectedException = null;

    public function withInitialEvents(Stream|callable ...$streams): self
    {
        $clone = clone $this;
        foreach ($streams as $stream) {
            $clone->initialStreams[] = $stream;
        }
        return $clone;
    }

    public function withInitialCommands(object|callable ...$commands): self
    {
        $clone = clone $this;
        foreach ($commands as $command) {
            $clone->initialCommands[] = $command;
        }
        return $clone;
    }

    public function doAction(callable $action): self
    {
        $clone = clone $this;
        $clone->actions[] = $action;
        return $clone;
    }

    public function dispatch(object|callable ...$commands): self
    {
        $clone = clone $this;
        foreach ($commands as $command) {
            $clone->commands[] = $command;
        }
        return $clone;
    }

    public function testEvents(callable $assertion): self
    {
        $clone = clone $this;
        $clone->eventsAssertions[] = $assertion;
        return $clone;
    }

    public function testProjections(callable $assertion): self
    {
        $clone = clone $this;
        $clone->projectionsAssertions[] = $assertion;
        return $clone;
    }

    public function testThat(callable $assertion): self
    {
        $clone = clone $this;
        $clone->customAssertions[] = $assertion;
        return $clone;
    }

    public function expectException(string $exceptionClass): self
    {
        $clone = clone $this;
        $clone->expectedException = $exceptionClass;
        return $clone;
    }

    public function run(
        EventBusInterface $eventBus,
        EventBusTraceMiddleware $eventBusTrace,
        EventStoreInterface $eventStore,
        DispatcherInterface $dispatcher,
        ProjectionStoreTraceMiddleware $projectionTrace,
    ): void {
        $catchedExceptions = [];
        $expectedExceptionThrown = false;

        $eventBusTrace->stopTracing();
        $projectionTrace->stopTracing();

        foreach ($this->initialStreams as $stream) {
            $eventStore->append($this->evaluate($stream));
            $eventBus->publish($this->evaluate($stream));
        }
        foreach ($this->initialCommands as $command) {
            $dispatcher->dispatch($this->evaluate($command));
        }

        $eventBusTrace->startTracing();
        $projectionTrace->startTracing();

        /* Commands and actions */
        foreach ($this->commands as $command) {
            try {
                $dispatcher->dispatch($this->evaluate($command));
            } catch (Exception $e) {
                if ($this->expectedException && ($e instanceof $this->expectedException)) {
                    $expectedExceptionThrown = true;
                } else {
                    throw $e;
                }
            }
        }
        foreach ($this->actions as $action) {
            try {
                $action();
            } catch (Exception $e) {
                if ($this->expectedException && ($e instanceof $this->expectedException)) {
                    $expectedExceptionThrown = true;
                } else {
                    throw $e;
                }
            }
        }

        /* Assert expected exception was thrown */
        if ($this->expectedException && !$expectedExceptionThrown) {
            throw new ExpectedExceptionWasNotThrownException($this->expectedException);
        }

        /* Assertions */
        $publishedStreams = $eventBusTrace->getTracedEventStreams();
        $eventBusTrace->clearTrace();
        foreach ($this->eventsAssertions as $assertion) {
            $assertion(new PublishedStreams($publishedStreams));
        }
        $updatedProjections = $projectionTrace->getTracedProjections();
        $projectionTrace->clearTrace();
        foreach ($this->projectionsAssertions as $assertion) {
            $assertion(new UpdatedProjections($updatedProjections));
        }
        foreach ($this->customAssertions as $assertion) {
            $assertion();
        }
    }

    private function evaluate(mixed $value): mixed
    {
        return is_callable($value) ? $value() : $value;
    }
}
