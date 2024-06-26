<?php

declare(strict_types=1);

namespace Backslash\PdoEventStore;

use Backslash\Domain\RecordedEvent;
use Backslash\Domain\RecordedEventStream;
use Backslash\EventStore\AdapterInterface;
use Backslash\EventStore\ConcurrencyException;
use Backslash\EventStore\InspectorInterface;
use Backslash\EventStore\Query\QueryInterface;
use Backslash\EventStore\StoredRecordedEventStream;
use Backslash\Pdo\PdoInterface;
use Backslash\Serializer\SerializerInterface;
use DateTimeImmutable;
use PDO;

final class PdoEventStoreAdapter implements AdapterInterface
{
    private PdoInterface $pdo;

    private Config $config;

    private SerializerInterface $eventSerializer;

    private SerializerInterface $identifiersSerializer;

    private SerializerInterface $metadataSerializer;

    /** @var callable */
    private $eventIdGenerator;

    private ?Driver $driver = null;

    public function __construct(
        PdoInterface $pdo,
        Config $config,
        SerializerInterface $eventSerializer,
        SerializerInterface $identifiersSerializer,
        SerializerInterface $metadataSerializer,
        callable $eventIdGenerator,
    ) {
        $this->pdo = $pdo;
        $this->config = $config;
        $this->eventSerializer = $eventSerializer;
        $this->identifiersSerializer = $identifiersSerializer;
        $this->metadataSerializer = $metadataSerializer;
        $this->eventIdGenerator = $eventIdGenerator;
        $this->detectDriver();
    }

    public function setupDatabase(): void
    {
        $sql = $this->driver->buildCreateTableStatement($this->config);
        $this->pdo->exec($sql);
    }

    public function fetch(?QueryInterface $query, int $fromSequence = 0): StoredRecordedEventStream
    {
        $whereClause = new QueryToWhereClause($query, $this->driver, $this->config);
        $sql = $this->driver->buildSelectStatement($fromSequence, $whereClause, $this->config);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($whereClause->getValues());

        $stream = new StoredRecordedEventStream();
        while ($row = $stmt->fetch()) {
            $stream = $stream
                ->withRecordedEvents($this->buildEventFromRow($row))
                ->withHighestSequence($row[$this->config->getAlias('sequence')]);
        }
        return $stream;
    }

    public function append(RecordedEventStream $stream, ?QueryInterface $concurrencyCheck, ?int $expectedSequence): void
    {
        if (!count($stream)) {
            return;
        }
        
        [$sql, $values] = $this->driver->buildInsertStatementAndValues(
            $stream,
            $concurrencyCheck,
            $expectedSequence,
            $this->eventSerializer,
            $this->identifiersSerializer,
            $this->metadataSerializer,
            $this->eventIdGenerator,
            $this->config,
        );

        $stmt = $this->pdo->prepare($sql);
        foreach ($values as $index => $value) {
            $stmt->bindValue($index + 1, $value);
        }
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            throw new ConcurrencyException();
        }
    }

    public function inspect(InspectorInterface $inspector): void
    {
        $whereClause = new QueryToWhereClause($inspector->getQuery(), $this->driver, $this->config);
        $sql = $this->driver->buildSelectStatement(0, $whereClause, $this->config);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($whereClause->getValues());

        while ($row = $stmt->fetch()) {
            $inspector->inspect($this->buildEventFromRow($row));
        }
    }

    public function purge(): void
    {
        $sql = $this->driver->buildTruncateTableStatement($this->config);
        $this->pdo->exec($sql);
    }

    private function detectDriver(): void
    {
        if (!$this->driver) {
            $this->driver = Driver::from($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
        }
    }

    private function buildEventFromRow(array $row): RecordedEvent
    {
        return RecordedEvent::create(
            $this->eventSerializer->deserialize(
                $row[$this->config->getAlias('event_payload')],
                $row[$this->config->getAlias('event_class')],
            ),
            $this->metadataSerializer->deserialize(
                $row[$this->config->getAlias('event_metadata')],
            ),
            new DateTimeImmutable($row[$this->config->getAlias('event_time')]),
        );
    }
}
