<?php

declare(strict_types=1);

namespace Backslash\PdoEventStore;

use Backslash\Event\RecordedEvent;
use Backslash\Event\RecordedEventStream;
use Backslash\EventNameResolver\EventNameResolverInterface;
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

    private EventNameResolverInterface $eventNameResolver;

    private SerializerInterface $eventSerializer;

    private SerializerInterface $metadataSerializer;

    /** @var callable */
    private $eventIdGenerator;

    private ?Driver $driver = null;

    public function __construct(
        PdoInterface $pdo,
        EventNameResolverInterface $eventNameResolver,
        SerializerInterface $eventSerializer,
        SerializerInterface $metadataSerializer,
        callable $eventIdGenerator,
    ) {
        $this->pdo = $pdo;
        $this->eventNameResolver = $eventNameResolver;
        $this->eventSerializer = $eventSerializer;
        $this->metadataSerializer = $metadataSerializer;
        $this->eventIdGenerator = $eventIdGenerator;
        $this->detectDriver();
    }

    public function setupDatabase(): void
    {
        foreach ($this->driver->buildCreateTableStatements() as $sql) {
            $this->pdo->exec($sql);
        }
    }

    public function fetch(?QueryInterface $query, int $fromSequence = 0): StoredRecordedEventStream
    {
        $whereClause = new QueryToWhereClause($query, $this->eventNameResolver);
        $sql = $this->driver->buildSelectStatement($fromSequence, $whereClause);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($whereClause->getValues());

        $stream = new StoredRecordedEventStream();
        while ($row = $stmt->fetch()) {
            $stream = $stream
                ->withRecordedEvents($this->buildEventFromRow($row))
                ->withHighestSequence((int) $row['sequence']);
        }
        return $stream;
    }

    public function append(RecordedEventStream $stream, ?QueryInterface $concurrencyCheck, ?int $expectedSequence): void
    {
        if (!count($stream)) {
            return;
        }

        [$eventStatement, $identifiersStatement, $metadataStatement] = $this->driver->buildInsertStatementsAndValues(
            $stream,
            $concurrencyCheck,
            $expectedSequence,
            $this->eventNameResolver,
            $this->eventSerializer,
            $this->metadataSerializer,
            $this->eventIdGenerator,
        );

        if ($this->execute($eventStatement) === 0) {
            throw new ConcurrencyException();
        }

        foreach ([$identifiersStatement, $metadataStatement] as $childStatement) {
            if ($childStatement !== null) {
                $this->execute($childStatement);
            }
        }
    }

    public function inspect(InspectorInterface $inspector): void
    {
        $whereClause = new QueryToWhereClause(
            $inspector->getQuery(),
            $this->eventNameResolver,
        );
        $sql = $this->driver->buildSelectStatement(0, $whereClause);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($whereClause->getValues());

        while ($row = $stmt->fetch()) {
            $inspector->inspect($this->buildEventFromRow($row));
        }
    }

    public function purge(): void
    {
        $this->pdo->exec($this->driver->buildTruncateTableStatement('event_store_identifiers'));
        $this->pdo->exec($this->driver->buildTruncateTableStatement('event_store_metadata'));
        $this->pdo->exec($this->driver->buildTruncateTableStatement('event_store'));
    }

    private function detectDriver(): void
    {
        if (!$this->driver) {
            $this->driver = Driver::from($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
        }
    }

    private function execute(array $statement): int
    {
        [$sql, $values] = $statement;
        $stmt = $this->pdo->prepare($sql);
        foreach ($values as $index => $value) {
            $stmt->bindValue($index + 1, $value);
        }
        $stmt->execute();
        return $stmt->rowCount();
    }

    private function buildEventFromRow(array $row): RecordedEvent
    {
        return RecordedEvent::create(
            $this->eventSerializer->deserialize(
                $row['event_payload'],
                $row['event_name'],
            ),
            $this->metadataSerializer->deserialize($row['event_metadata']),
            new DateTimeImmutable($row['event_time']),
        );
    }
}
