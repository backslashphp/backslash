<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Aggregate\RecordedEvent;
use Backslash\Aggregate\Stream;
use RuntimeException;

final class InMemoryEventStoreAdapter implements AdapterInterface
{
    private array $storage = [];

    /** @var array[] */
    private array $chronologicalIndex = [];

    public function fetch(string $aggregateId, string $aggregateType, int $fromVersion = 0): Stream
    {
        if ($this->streamExists($aggregateId, $aggregateType)) {
            $stream = new Stream($aggregateId, $aggregateType);
            /** @var RecordedEvent $recordedEvent */
            foreach (
                $this->storage[$this->resolveId(
                    $aggregateId,
                    $aggregateType,
                )]['recordedEvents'] as $recordedEvent
            ) {
                if ($recordedEvent->getVersion() >= $fromVersion) {
                    $stream = $stream->withRecordedEvent($recordedEvent);
                }
            }
            return $stream;
        }
        throw StreamNotFoundException::forId($aggregateId, $aggregateType);
    }

    public function streamExists(string $aggregateId, string $aggregateType): bool
    {
        return array_key_exists($this->resolveId($aggregateId, $aggregateType), $this->storage);
    }

    public function append(Stream $stream, ?int $expectedVersion = null): void
    {
        if (count($stream->getRecordedEvents()) === 0) {
            return;
        }

        if ($expectedVersion > 0) {
            $version = $this->getVersion($stream->getAggregateId(), $stream->getAggregateType());
            if ($version != $expectedVersion) {
                $message = sprintf(
                    'Version for stream %s-%s is %s, expected %s.',
                    $stream->getAggregateType(),
                    $stream->getAggregateId(),
                    $version,
                    $expectedVersion,
                );
                throw new ConcurrencyException($message);
            }
        }

        $key = $this->resolveId($stream->getAggregateId(), $stream->getAggregateType());
        if (!array_key_exists($key, $this->storage)) {
            $this->storage[$key] = [
                'id' => $stream->getAggregateId(),
                'type' => $stream->getAggregateType(),
                'recordedEvents' => [],
            ];
        }

        /** @var RecordedEvent $recordedEvent */
        foreach ($stream->getRecordedEvents() as $recordedEvent) {
            $version = $recordedEvent->getVersion();
            if (isset($this->storage[$key]['recordedEvents'][$version])) {
                $message = sprintf(
                    'An event with version %d is already recorded for stream ID %s.',
                    $version,
                    $stream->getAggregateId(),
                );
                throw new ConcurrencyException($message);
            }
            $this->storage[$key]['recordedEvents'][$version] = $recordedEvent;
            $this->chronologicalIndex[] = [$key, $version];
        }
    }

    public function getVersion(string $aggregateId, string $aggregateType): int
    {
        if ($this->streamExists($aggregateId, $aggregateType)) {
            return (int) max(
                array_keys($this->storage[$this->resolveId($aggregateId, $aggregateType)]['recordedEvents']),
            );
        }
        throw StreamNotFoundException::forId($aggregateId, $aggregateType);
    }

    public function purge(): void
    {
        $this->storage = [];
        $this->chronologicalIndex = [];
    }

    public function inspect(InspectorInterface $inspector): void
    {
        if ($inspector->getFilter()->isReverse() || $inspector->getFilter()->getLimit()) {
            throw new RuntimeException('Not implemented.');
        }
        foreach ($this->chronologicalIndex as $pair) {
            $key = $pair[0];
            $version = $pair[1];
            /** @var RecordedEvent $recordedEvent */
            $recordedEvent = $this->storage[$key]['recordedEvents'][$version];
            if (
                empty($inspector->getFilter()->getClasses())
                || in_array($recordedEvent->getEvent()::class, $inspector->getFilter()->getClasses())
            ) {
                $inspector->inspect($this->storage[$key]['id'], $this->storage[$key]['type'], $recordedEvent);
            }
        }
    }

    private function resolveId(string $aggregateId, string $aggregateType): string
    {
        return $aggregateType . '-' . $aggregateId;
    }
}
