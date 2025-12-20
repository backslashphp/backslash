<?php

declare(strict_types=1);

namespace Backslash\Scenario;

use Backslash\CommandDispatcher\DispatcherInterface;
use Backslash\Event\EventInterface;
use Backslash\Event\Metadata;
use Backslash\Event\RecordedEvent;
use Backslash\Event\RecordedEventStream;
use Backslash\EventBus\EventBusInterface;
use Backslash\EventStore\EventStoreInterface;
use DateTimeImmutable;
use Exception;

final class Play
{
    /** @var RecordedEventStream|callable|null */
    private mixed $initialEvents = null;

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

    private ?string $expectedExceptionMessage = null;

    public function withInitialEvents(EventInterface|RecordedEventStream|callable ...$events): self
    {
        $clone = clone $this;

        // If single argument and it's a RecordedEventStream or callable, use it directly
        if (count($events) === 1 && ($events[0] instanceof RecordedEventStream || is_callable($events[0]))) {
            $clone->initialEvents = $events[0];
            return $clone;
        }

        // Otherwise, wrap EventInterface instances in RecordedEvent
        $recordedEvents = array_map(
            fn($event) => RecordedEvent::create($event, new Metadata(), new DateTimeImmutable()),
            $events
        );

        $clone->initialEvents = new RecordedEventStream(...$recordedEvents);
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

    public function expectExceptionMessage(string $message): self
    {
        $clone = clone $this;
        $clone->expectedExceptionMessage = $message;
        return $clone;
    }

    public function run(
        EventBusInterface $eventBus,
        EventBusTraceMiddleware $eventBusTrace,
        EventStoreInterface $eventStore,
        DispatcherInterface $dispatcher,
        ProjectionStoreTraceMiddleware $projectionTrace,
    ): void {
        $expectedExceptionClassThrown = false;
        $expectedExceptionMessageThrown = false;

        $eventBusTrace->blockPublishing();
        $eventBusTrace->stopTracing();
        $projectionTrace->stopTracing();

        if ($this->initialEvents) {
            $eventStore->append($this->evaluate($this->initialEvents), null, null);
            $eventBus->publish($this->evaluate($this->initialEvents));
        }
        foreach ($this->initialCommands as $command) {
            $dispatcher->dispatch($this->evaluate($command));
        }

        $eventBusTrace->unblockPublishing();
        $eventBusTrace->startTracing();
        $projectionTrace->startTracing();

        /* Commands and actions */
        foreach ($this->commands as $command) {
            try {
                $dispatcher->dispatch($this->evaluate($command));
            } catch (Exception $e) {
                $catched = false;
                if ($this->expectedException && ($e instanceof $this->expectedException)) {
                    $expectedExceptionClassThrown = true;
                    $catched = true;
                }
                if ($e->getMessage() ===  $this->expectedExceptionMessage) {
                    $expectedExceptionMessageThrown = true;
                    $catched = true;
                }
                if (!$catched) {
                    throw $e;
                }
            }
        }
        foreach ($this->actions as $action) {
            try {
                $action();
            } catch (Exception $e) {
                $catched = false;
                if ($this->expectedException && ($e instanceof $this->expectedException)) {
                    $expectedExceptionClassThrown = true;
                    $catched = true;
                }
                if ($e->getMessage() ===  $this->expectedExceptionMessage) {
                    $expectedExceptionMessageThrown = true;
                    $catched = true;
                }
                if (!$catched) {
                    throw $e;
                }
            }
        }

        /* Assert expected exception was thrown */
        if ($this->expectedException && !$expectedExceptionClassThrown) {
            throw new ExpectedExceptionWasNotThrownException(
                sprintf('Exception %s was expected to be thrown.', $this->expectedException),
            );
        }
        if ($this->expectedExceptionMessage && !$expectedExceptionMessageThrown) {
            throw new ExpectedExceptionMessageWasNotThrownException(
                sprintf('An exception with message "%s" was expected to be thrown.', $this->expectedExceptionMessage),
            );
        }

        /* Assertions */
        $publishedStreams = $eventBusTrace->getTracedEvents();
        $eventBusTrace->clearTrace();
        foreach ($this->eventsAssertions as $assertion) {
            $assertion(new PublishedEvents($publishedStreams));
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
