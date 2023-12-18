<?php

declare(strict_types=1);

namespace Backslash\PdoEventStore;

use Backslash\Aggregate\EventInterface;
use Backslash\Aggregate\RecordedEvent;
use Backslash\Aggregate\Stream;
use Backslash\EventStore\AdapterInterface;
use Backslash\EventStore\ConcurrencyException;
use Backslash\EventStore\InspectorInterface;
use Backslash\EventStore\StreamNotFoundException;
use Backslash\Serializer\SerializerInterface;
use Backslash\Pdo\PdoInterface;
use DateTimeImmutable;
use Exception;
use PDO;
use PDOException;

final class PdoEventStoreAdapter implements AdapterInterface
{
    private PdoInterface $pdo;

    private Config $config;

    private SerializerInterface $eventSerializer;

    private SerializerInterface $metadataSerializer;

    /** @var callable */
    private $eventIdGenerator;

    public function __construct(
        PdoInterface $pdo,
        Config $config,
        SerializerInterface $eventSerializer,
        SerializerInterface $metadataSerializer,
        callable $eventIdGenerator,
    ) {
        $this->pdo = $pdo;
        $this->config = $config;
        $this->eventSerializer = $eventSerializer;
        $this->metadataSerializer = $metadataSerializer;
        $this->eventIdGenerator = $eventIdGenerator;
    }

    public function fetch(string $aggregateId, string $aggregateType, int $fromVersion = 0): Stream
    {
        $selectColumns = [
            '`' . $this->config->getAlias('aggregate_type') . '`',
            '`' . $this->config->getAlias('aggregate_id') . '`',
            '`' . $this->config->getAlias('aggregate_version') . '`',
            '`' . $this->config->getAlias('event_id') . '`',
            '`' . $this->config->getAlias('event_class') . '`',
            '`' . $this->config->getAlias('event_metadata') . '`',
            '`' . $this->config->getAlias('event_payload') . '`',
            '`' . $this->config->getAlias('event_time') . '`',
        ];
        $sql = sprintf(
            'select %s from `%s` where `%s` = :aggregateType and `%s` = :aggregateId and `%s` >= :aggregateVersion order by `%s` asc',
            implode(',', $selectColumns),
            $this->config->getTable(),
            $this->config->getAlias('aggregate_type'),
            $this->config->getAlias('aggregate_id'),
            $this->config->getAlias('aggregate_version'),
            $this->config->getAlias('aggregate_version'),
        );
        $query = $this->pdo->prepare($sql);
        $query->execute(
            [
                ':aggregateType' => $aggregateType,
                ':aggregateId' => $aggregateId,
                ':aggregateVersion' => $fromVersion,
            ],
        );
        $rows = $query->fetchAll(PDO::FETCH_ASSOC);
        if (!count($rows) && ($fromVersion === 0)) {
            throw new StreamNotFoundException($aggregateId);
        }

        $stream = new Stream($aggregateId, $aggregateType);
        foreach ($rows as $row) {
            $stream = $stream->withRecordedEvent($this->buildEventFromRow($row));
        }

        return $stream;
    }

    public function streamExists(string $aggregateId, string $aggregateType): bool
    {
        $sql = sprintf(
            'select count(*) `event_count` from `%s` where `%s` = :aggregateType and `%s` = :aggregateId',
            $this->config->getTable(),
            $this->config->getAlias('aggregate_type'),
            $this->config->getAlias('aggregate_id'),
        );
        $query = $this->pdo->prepare($sql);
        $query->execute(
            [
                ':aggregateType' => $aggregateType,
                ':aggregateId' => $aggregateId,
            ],
        );
        $count = $query->fetchAll(PDO::FETCH_ASSOC)[0]['event_count'];
        return (int) $count > 0;
    }

    public function append(Stream $stream, ?int $expectedVersion = null): void
    {
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

        $insertColumns = [
            '`' . $this->config->getAlias('aggregate_type') . '`',
            '`' . $this->config->getAlias('aggregate_id') . '`',
            '`' . $this->config->getAlias('aggregate_version') . '`',
            '`' . $this->config->getAlias('event_id') . '`',
            '`' . $this->config->getAlias('event_class') . '`',
            '`' . $this->config->getAlias('event_metadata') . '`',
            '`' . $this->config->getAlias('event_payload') . '`',
            '`' . $this->config->getAlias('event_time') . '`',
        ];
        $sql = sprintf(
            'insert into `%s` (%s) values (:aggregateType, :aggregateId, :version, :id, :class, :metadata, :payload, :time)',
            $this->config->getTable(),
            implode(',', $insertColumns),
        );
        $query = $this->pdo->prepare($sql);
        $eventIdGenerator = $this->eventIdGenerator;
        foreach ($stream->getRecordedEvents() as $recordedEvent) {
            /** @var RecordedEvent $recordedEvent */
            $values = $this->buildValuesFromEvent($recordedEvent);
            $metadata = $this->metadataSerializer->serialize($values['metadata']);
            $payload = $this->eventSerializer->serialize($values['event']);
            try {
                $query->execute(
                    [
                        ':aggregateType' => $stream->getAggregateType(),
                        ':aggregateId' => $stream->getAggregateId(),
                        ':version' => $values['version'],
                        ':id' => (string) $eventIdGenerator(),
                        ':class' => $values['class'],
                        ':metadata' => $metadata,
                        ':payload' => $payload,
                        ':time' => $values['time'],
                    ],
                );
            } catch (Exception $e) {
                if ($e instanceof PDOException) {
                    throw new ConcurrencyException(
                        sprintf(
                            'Version %s for stream %s-%s already exists.',
                            $values['version'],
                            $stream->getAggregateType(),
                            $stream->getAggregateId(),
                        ),
                    );
                }
            }
        }
    }

    public function getVersion(string $aggregateId, string $aggregateType): int
    {
        $sql = sprintf(
            'select max(`%s`) `max_aggregate_version` from `%s` where `%s` = :aggregateType and `%s` = :aggregateId',
            $this->config->getAlias('aggregate_version'),
            $this->config->getTable(),
            $this->config->getAlias('aggregate_type'),
            $this->config->getAlias('aggregate_id'),
        );
        $query = $this->pdo->prepare($sql);
        $query->execute(
            [
                ':aggregateType' => $aggregateType,
                ':aggregateId' => $aggregateId,
            ],
        );
        $version = $query->fetchAll(PDO::FETCH_ASSOC)[0]['max_aggregate_version'];
        if (!$version) {
            throw new StreamNotFoundException($aggregateId);
        }

        return (int) $version;
    }

    public function inspect(InspectorInterface $inspector): void
    {
        $reverse = $inspector->getFilter()->isReverse();
        $max = $inspector->getFilter()->getLimit();
        $filteredEvents = $inspector->getFilter()->getClasses();

        if (count($filteredEvents)) {
            $questionMarks = str_repeat('?,', count($filteredEvents) - 1) . '?';
            $whereClause = sprintf(
                'where `%s` in (%s)',
                $this->config->getAlias('event_class'),
                $questionMarks,
            );
        } else {
            $whereClause = '';
        }

        $limitClause = $max ? 'limit ' . $max : '';
        $sql = sprintf(
            'select * from `%s` %s order by `%s` %s %s',
            $this->config->getTable(),
            $whereClause,
            $this->config->getAlias('sequence'),
            $reverse ? 'desc' : 'asc',
            $limitClause,
        );
        $query = $this->pdo->prepare($sql);
        $query->execute($filteredEvents);
        while ($row = $query->fetch()) {
            $event = [
                'aggregate_id' => $row[$this->config->getAlias('aggregate_id')],
                'aggregate_type' => $row[$this->config->getAlias('aggregate_type')],
                'event' => $this->buildEventFromRow($row),
            ];
            $inspector->inspect($event['aggregate_id'], $event['aggregate_type'], $event['event']);
        }
    }

    public function purge(): void
    {
        $sql = sprintf('delete from `%s` where 1=1', $this->config->getTable());
        $this->pdo->exec($sql);
    }

    private function buildEventFromRow(array $row): RecordedEvent
    {
        $version = (int) $row[$this->config->getAlias('aggregate_version')];
        $metadata = $this->metadataSerializer->deserialize($row[$this->config->getAlias('event_metadata')]);
        $class = $row[$this->config->getAlias('event_class')];
        /** @var EventInterface $event */
        $event = $this->eventSerializer->deserialize($row[$this->config->getAlias('event_payload')], $class);
        $recordTime = new DateTimeImmutable($row[$this->config->getAlias('event_time')]);

        return RecordedEvent::create($event, $version, $metadata, $recordTime);
    }

    private function buildValuesFromEvent(RecordedEvent $recordedEvent): array
    {
        $values = [
            'version' => $recordedEvent->getVersion(),
            'metadata' => $recordedEvent->getMetadata(),
            'class' => $recordedEvent->getEvent()::class,
            'event' => $recordedEvent->getEvent(),
            'time' => $recordedEvent->getRecordTime()->format('Y-m-d\TH:i:s.uP'),
        ];

        return $values;
    }
}
