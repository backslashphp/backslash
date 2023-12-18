<?php

declare(strict_types=1);

namespace Backslash\EventSourcingAggregateStore;

use Backslash\Aggregate\AggregateFactory;
use Backslash\Aggregate\AggregateInterface;
use Backslash\Aggregate\Stream;
use Backslash\AggregateStore\AdapterInterface;
use Backslash\AggregateStore\AggregateNotFoundException;
use Backslash\EventBus\EventBusInterface;
use Backslash\EventStore\EventStoreInterface;
use Backslash\EventStore\StreamNotFoundException;
use RuntimeException;
use UnexpectedValueException;

final class EventSourcingAggregateStoreAdapter implements AdapterInterface
{
    private AggregateFactory $aggregateFactory;

    private EventStoreInterface $eventStore;

    private EventBusInterface $eventBus;

    public function __construct(
        AggregateFactory $aggregateFactory,
        EventStoreInterface $eventStore,
        EventBusInterface $eventBus,
    ) {
        $this->aggregateFactory = $aggregateFactory;
        $this->eventStore = $eventStore;
        $this->eventBus = $eventBus;
    }

    public function find(string $aggregateId, string $aggregateType): AggregateInterface
    {
        if ($this->getExpectedAggregateType() !== $aggregateType) {
            throw new UnexpectedValueException('Unexpected aggregate type.');
        }
        try {
            $stream = $this->eventStore->fetch($aggregateId, $aggregateType);
        } catch (StreamNotFoundException $e) {
            throw AggregateNotFoundException::forAggregate($aggregateId, $aggregateType, $e);
        }
        return $this->aggregateFactory->rebuildFromStream($stream);
    }

    public function has(string $aggregateId, string $aggregateType): bool
    {
        return $this->eventStore->streamExists($aggregateId, $aggregateType);
    }

    public function store(AggregateInterface $aggregate): void
    {
        $stream = new Stream($aggregate->getAggregateId(), $aggregate::getType());
        $recordedEvents = $aggregate->pullNewEvents();
        foreach ($recordedEvents->getRecordedEvents() as $recordedEvent) {
            $stream = $stream->withRecordedEvent($recordedEvent);
        }
        $this->eventStore->append($stream);
        $this->eventBus->publish($stream);
    }

    public function remove(string $aggregateId, string $aggregateType): void
    {
        throw new RuntimeException('Unsupported operation.');
    }

    public function purge(): void
    {
        throw new RuntimeException('Unsupported operation.');
    }

    private function getExpectedAggregateType(): string
    {
        /** @var AggregateInterface $class */
        $class = $this->aggregateFactory->getAggregateRootClass();
        return $class::getType();
    }
}
