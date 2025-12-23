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
use Backslash\Repository\RepositoryInterface;
use DateTimeImmutable;
use Exception;
use ReflectionFunction;
use ReflectionNamedType;

final class Play
{
    /** @var RecordedEventStream|callable|null */
    private mixed $givenEvents = null;

    /** @var array<object|callable> */
    private array $givenCommands = [];

    /** @var array<object|callable> */
    private array $whenCommands = [];

    /** @var callable[] */
    private array $whenActions = [];

    /** @var callable[] */
    private array $eventsAssertions = [];

    /** @var callable[] */
    private array $projectionsAssertions = [];

    /** @var callable[] */
    private array $customAssertions = [];

    /** @var callable[] */
    private array $thenAssertions = [];

    private ?string $thenExpectedException = null;

    private ?string $thenExpectedExceptionMessage = null;

    private EventPublishingMode $eventPublishingMode = EventPublishingMode::DETECT;

    /**
     * @deprecated Use given() instead. Will be removed in Backslash 3.x
     */
    public function withInitialEvents(EventInterface|RecordedEventStream|callable ...$events): self
    {
        $clone = clone $this;

        // If single argument and it's a RecordedEventStream or callable, use it directly
        if (count($events) === 1 && ($events[0] instanceof RecordedEventStream || is_callable($events[0]))) {
            $clone->givenEvents = $events[0];
            return $clone;
        }

        // Otherwise, wrap EventInterface instances in RecordedEvent
        $recordedEvents = array_map(
            fn ($event) => RecordedEvent::create($event, new Metadata(), new DateTimeImmutable()),
            $events,
        );

        $clone->givenEvents = new RecordedEventStream(...$recordedEvents);
        return $clone;
    }

    /**
     * @deprecated Use given() instead. Will be removed in Backslash 3.x
     */
    public function withInitialCommands(object|callable ...$commands): self
    {
        $clone = clone $this;
        foreach ($commands as $command) {
            $clone->givenCommands[] = $command;
        }
        return $clone;
    }

    /**
     * @deprecated Use when() instead. Will be removed in Backslash 3.x
     */
    public function doAction(callable $action): self
    {
        $clone = clone $this;
        $clone->whenActions[] = $action;
        return $clone;
    }

    /**
     * @deprecated Use when() instead. Will be removed in Backslash 3.x
     */
    public function dispatch(object|callable ...$commands): self
    {
        $clone = clone $this;
        foreach ($commands as $command) {
            $clone->whenCommands[] = $command;
        }
        return $clone;
    }

    /**
     * @deprecated Use then() instead. Will be removed in Backslash 3.x
     */
    public function testEvents(callable $assertion): self
    {
        $clone = clone $this;
        $clone->eventsAssertions[] = $assertion;
        return $clone;
    }

    /**
     * @deprecated Use then() instead. Will be removed in Backslash 3.x
     */
    public function testProjections(callable $assertion): self
    {
        $clone = clone $this;
        $clone->projectionsAssertions[] = $assertion;
        return $clone;
    }

    /**
     * @deprecated Use then() instead. Will be removed in Backslash 3.x
     */
    public function testThat(callable $assertion): self
    {
        $clone = clone $this;
        $clone->customAssertions[] = $assertion;
        return $clone;
    }

    /**
     * @deprecated Use thenExpectException() instead. Will be removed in Backslash 3.x
     */
    public function expectException(string $exceptionClass): self
    {
        $clone = clone $this;
        $clone->thenExpectedException = $exceptionClass;
        return $clone;
    }

    /**
     * @deprecated Use thenExpectExceptionMessage() instead. Will be removed in Backslash 3.x
     */
    public function expectExceptionMessage(string $message): self
    {
        $clone = clone $this;
        $clone->thenExpectedExceptionMessage = $message;
        return $clone;
    }

    public function withProjections(): self
    {
        $clone = clone $this;
        $clone->eventPublishingMode = EventPublishingMode::ALWAYS;
        return $clone;
    }

    public function withoutProjections(): self
    {
        $clone = clone $this;
        $clone->eventPublishingMode = EventPublishingMode::NEVER;
        return $clone;
    }

    public function given(object|callable ...$items): self
    {
        if (empty($items)) {
            return $this;
        }

        $events = [];
        $commands = [];

        foreach ($items as $item) {
            if ($item instanceof EventInterface || $item instanceof RecordedEventStream || is_callable($item)) {
                $events[] = $item;
            } else {
                $commands[] = $item;
            }
        }

        $clone = $this;
        if (!empty($events)) {
            $clone = $clone->withInitialEvents(...$events);
        }
        if (!empty($commands)) {
            $clone = $clone->withInitialCommands(...$commands);
        }

        return $clone;
    }

    public function when(object|callable ...$items): self
    {
        $clone = clone $this;
        foreach ($items as $item) {
            if (is_callable($item)) {
                $clone->whenActions[] = $item;
            } else {
                $clone->whenCommands[] = $item;
            }
        }
        return $clone;
    }

    public function then(callable $assertion): self
    {
        $clone = clone $this;
        $clone->thenAssertions[] = $assertion;
        return $clone;
    }

    public function thenExpectException(string $exceptionClass): self
    {
        return $this->expectException($exceptionClass);
    }

    public function thenExpectExceptionMessage(string $message): self
    {
        return $this->expectExceptionMessage($message);
    }

    public function run(
        EventBusInterface $eventBus,
        ScenarioEventBusMiddleware $eventBusMiddleware,
        EventStoreInterface $eventStore,
        DispatcherInterface $dispatcher,
        ScenarioProjectionStoreMiddleware $projectionMiddleware,
        RepositoryInterface $repository,
    ): void {
        $expectedExceptionClassThrown = false;
        $expectedExceptionMessageThrown = false;

        // Detect if projections are needed
        $needsProjections = $this->needsProjections();

        // GIVEN - Setup phase
        $eventBusMiddleware->stopTracing();
        $eventBusMiddleware->unblockPublishing(); // Reset to clean state
        $projectionMiddleware->stopTracing();

        if (!$needsProjections) {
            $eventBusMiddleware->blockPublishing();
        }

        if ($this->givenEvents) {
            $eventStore->append($this->evaluate($this->givenEvents), null, null);
            $eventBus->publish($this->evaluate($this->givenEvents));
        }
        foreach ($this->givenCommands as $command) {
            $dispatcher->dispatch($this->evaluate($command));
        }

        // WHEN - Execution phase
        $eventBusMiddleware->startTracing();
        $projectionMiddleware->startTracing();

        // Only unblock if projections are needed
        if ($needsProjections) {
            $eventBusMiddleware->unblockPublishing();
        }

        /* Commands and actions */
        foreach ($this->whenCommands as $command) {
            try {
                $dispatcher->dispatch($this->evaluate($command));
            } catch (Exception $e) {
                $catched = false;
                if ($this->thenExpectedException && ($e instanceof $this->thenExpectedException)) {
                    $expectedExceptionClassThrown = true;
                    $catched = true;
                }
                if ($e->getMessage() === $this->thenExpectedExceptionMessage) {
                    $expectedExceptionMessageThrown = true;
                    $catched = true;
                }
                if (!$catched) {
                    throw $e;
                }
            }
        }
        foreach ($this->whenActions as $action) {
            try {
                $this->invokeAction($action, $repository, $dispatcher);
            } catch (Exception $e) {
                $catched = false;
                if ($this->thenExpectedException && ($e instanceof $this->thenExpectedException)) {
                    $expectedExceptionClassThrown = true;
                    $catched = true;
                }
                if ($e->getMessage() === $this->thenExpectedExceptionMessage) {
                    $expectedExceptionMessageThrown = true;
                    $catched = true;
                }
                if (!$catched) {
                    throw $e;
                }
            }
        }

        /* Assert expected exception was thrown */
        if ($this->thenExpectedException && !$expectedExceptionClassThrown) {
            throw new ExpectedExceptionWasNotThrownException(
                sprintf('Exception %s was expected to be thrown.', $this->thenExpectedException),
            );
        }
        if ($this->thenExpectedExceptionMessage && !$expectedExceptionMessageThrown) {
            throw new ExpectedExceptionMessageWasNotThrownException(
                sprintf('An exception with message "%s" was expected to be thrown.', $this->thenExpectedExceptionMessage),
            );
        }

        /* THEN - Assertions */
        $publishedStreams = $eventBusMiddleware->getTracedEvents();
        $eventBusMiddleware->clearTrace();
        $publishedEvents = new PublishedEvents($publishedStreams);

        $updatedProjections = new UpdatedProjections($projectionMiddleware->getTracedProjections());
        $projectionMiddleware->clearTrace();

        // Old-style assertions (for backward compatibility)
        foreach ($this->eventsAssertions as $assertion) {
            $assertion($publishedEvents);
        }
        foreach ($this->projectionsAssertions as $assertion) {
            $assertion($updatedProjections);
        }
        foreach ($this->customAssertions as $assertion) {
            $assertion();
        }

        // New-style then() assertions with automatic routing
        foreach ($this->thenAssertions as $assertion) {
            $this->invokeAssertion($assertion, $publishedEvents, $updatedProjections, $repository);
        }
    }

    private function evaluate(mixed $value): mixed
    {
        return is_callable($value) ? $value() : $value;
    }

    private function needsProjections(): bool
    {
        // Explicit override
        if ($this->eventPublishingMode !== EventPublishingMode::DETECT) {
            return $this->eventPublishingMode === EventPublishingMode::ALWAYS;
        }

        // Auto-detect from then() assertions
        foreach ($this->thenAssertions as $assertion) {
            $reflection = new ReflectionFunction($assertion);
            $params = $reflection->getParameters();

            if (!empty($params)) {
                $type = $params[0]->getType();
                if ($type instanceof ReflectionNamedType && $type->getName() === UpdatedProjections::class) {
                    return true;
                }
            }
        }

        // Also check old-style projectionsAssertions for backward compatibility
        if (!empty($this->projectionsAssertions)) {
            return true;
        }

        return false;
    }

    private function invokeAssertion(
        callable            $assertion,
        PublishedEvents     $events,
        UpdatedProjections  $projections,
        RepositoryInterface $repository,
    ): void {
        $reflection = new ReflectionFunction($assertion);
        $params = $reflection->getParameters();

        if (empty($params)) {
            $assertion();
            return;
        }

        $type = $params[0]->getType();
        if (!($type instanceof ReflectionNamedType)) {
            $assertion();
            return;
        }

        match ($type->getName()) {
            PublishedEvents::class => $assertion($events),
            UpdatedProjections::class => $assertion($projections),
            RepositoryInterface::class => $assertion($repository),
            default => $assertion(),
        };
    }

    private function invokeAction(callable $action, RepositoryInterface $repository, DispatcherInterface $dispatcher): void
    {
        $reflection = new ReflectionFunction($action);
        $params = $reflection->getParameters();

        if (empty($params)) {
            // Closure returns a command
            $command = $action();
            if ($command !== null) {
                $dispatcher->dispatch($command);
            }
            return;
        }

        $type = $params[0]->getType();
        if ($type instanceof ReflectionNamedType && $type->getName() === RepositoryInterface::class) {
            // Closure works with repository
            $action($repository);
        } else {
            // Fallback: just invoke
            $action();
        }
    }
}
