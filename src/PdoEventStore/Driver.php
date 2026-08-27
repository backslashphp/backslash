<?php

declare(strict_types=1);

namespace Backslash\PdoEventStore;

use Backslash\Event\RecordedEvent;
use Backslash\Event\RecordedEventStream;
use Backslash\EventNameResolver\EventNameResolverInterface;
use Backslash\EventStore\Query\QueryInterface;
use Backslash\Serializer\SerializerInterface;

enum Driver: string
{
    public function buildCreateTableStatements(): array
    {
        return match ($this) {
            self::MYSQL => [
                'CREATE TABLE IF NOT EXISTS `event_store` (`sequence` BIGINT NOT NULL AUTO_INCREMENT, `event_uid` VARCHAR(36) NOT NULL, `event_name` varchar(255) NOT NULL, `event_payload` JSON NOT NULL CHECK (JSON_VALID(`event_payload`)), `event_metadata` JSON NOT NULL CHECK (JSON_VALID(`event_metadata`)), `event_time` varchar(255) NOT NULL, PRIMARY KEY (`sequence`), CONSTRAINT `event_uid_unique` UNIQUE KEY (`event_uid`), KEY `event_store_event_name_idx` (`event_name`), KEY `event_store_event_time_idx` (`event_time`))',
                'CREATE TABLE IF NOT EXISTS `event_store_identifiers` (`event_uid` VARCHAR(36) NOT NULL, `name` VARCHAR(255) NOT NULL, `value` VARCHAR(255) NOT NULL, KEY `event_store_identifiers_name_value_idx` (`name`, `value`, `event_uid`))',
                'CREATE TABLE IF NOT EXISTS `event_store_metadata` (`event_uid` VARCHAR(36) NOT NULL, `name` VARCHAR(255) NOT NULL, `value` VARCHAR(255) NOT NULL, KEY `event_store_metadata_name_value_idx` (`name`, `value`, `event_uid`))',
            ],
            self::SQLITE => [
                'CREATE TABLE IF NOT EXISTS `event_store` (`sequence` INTEGER PRIMARY KEY AUTOINCREMENT, `event_uid` TEXT NOT NULL, `event_name` TEXT NOT NULL, `event_payload` TEXT NOT NULL, `event_metadata` TEXT NOT NULL, `event_time` TEXT NOT NULL, UNIQUE(`event_uid`))',
                'CREATE INDEX IF NOT EXISTS `event_store_event_name_idx` ON `event_store` (`event_name`)',
                'CREATE INDEX IF NOT EXISTS `event_store_event_time_idx` ON `event_store` (`event_time`)',
                'CREATE TABLE IF NOT EXISTS `event_store_identifiers` (`event_uid` TEXT NOT NULL, `name` TEXT NOT NULL, `value` TEXT NOT NULL)',
                'CREATE INDEX IF NOT EXISTS `event_store_identifiers_name_value_idx` ON `event_store_identifiers` (`name`, `value`, `event_uid`)',
                'CREATE TABLE IF NOT EXISTS `event_store_metadata` (`event_uid` TEXT NOT NULL, `name` TEXT NOT NULL, `value` TEXT NOT NULL)',
                'CREATE INDEX IF NOT EXISTS `event_store_metadata_name_value_idx` ON `event_store_metadata` (`name`, `value`, `event_uid`)',
            ],
        };
    }

    public function buildTruncateTableStatement(string $tableName): string
    {
        return sprintf('DELETE FROM `%s`', $tableName);
    }

    public function buildSelectStatement(int $fromSequence, QueryToWhereClause $where): string
    {
        return sprintf(
            'SELECT `event_store`.* FROM `event_store` WHERE `event_store`.`sequence` >= %d AND %s ORDER BY `event_store`.`sequence` ASC',
            $fromSequence,
            $where->getStatement(),
        );
    }

    public function buildInsertStatementsAndValues(
        RecordedEventStream $stream,
        ?QueryInterface $concurrencyCheck,
        ?int $expectedSequence,
        EventNameResolverInterface $eventNameResolver,
        SerializerInterface $eventSerializer,
        SerializerInterface $metadataSerializer,
        callable $eventIdGenerator,
    ): array {
        $values = [];
        $unionSelects = [];
        $identifierRows = [];
        $metadataRows = [];

        /** @var RecordedEvent $recordedEvent */
        foreach ($stream as $index => $recordedEvent) {
            $eventUid = $eventIdGenerator();

            $unionSelects[] = sprintf('SELECT %d `union_index`, ? `col1`, ? `col2`, ? `col3`, ? `col4`, ? `col5`', $index);
            $values = array_merge($values, [
                $eventUid,
                $eventNameResolver->resolveName($recordedEvent->getEvent()::class),
                $eventSerializer->serialize($recordedEvent->getEvent()),
                $metadataSerializer->serialize($recordedEvent->getMetadata()),
                $recordedEvent->getRecordTime()->format('Y-m-d\TH:i:s.uP'),
            ]);

            foreach ($recordedEvent->getEvent()->getIdentifiers()->toArray() as $name => $value) {
                foreach ((array) $value as $singleValue) {
                    $identifierRows[] = [$eventUid, $name, $singleValue];
                }
            }

            foreach ($recordedEvent->getMetadata()->toArray() as $name => $value) {
                $metadataRows[] = [$eventUid, $name, $value];
            }
        }
        $unionSelects = implode(' UNION ', $unionSelects) . ' ORDER BY `union_index` ASC';

        $concurrencyCheckWhere = new QueryToWhereClause($concurrencyCheck, $eventNameResolver);
        $values = array_merge($values, $concurrencyCheckWhere->getValues());

        $eventStatement = sprintf(
            'INSERT INTO `event_store` (`event_uid`, `event_name`, `event_payload`, `event_metadata`, `event_time`) SELECT `col1`, `col2`, `col3`, `col4`, `col5` FROM (%s) `union_selects` WHERE (SELECT IFNULL(MAX(`sequence`), 0) FROM `event_store` WHERE 1=1 AND %s) %s',
            $unionSelects,
            $concurrencyCheckWhere->getStatement(),
            $expectedSequence ? sprintf('= %d', $expectedSequence) : '>= 0',
        );

        return [
            [$eventStatement, $values],
            $this->buildChildInsertStatementAndValues('event_store_identifiers', $identifierRows),
            $this->buildChildInsertStatementAndValues('event_store_metadata', $metadataRows),
        ];
    }

    private function buildChildInsertStatementAndValues(string $tableName, array $rows): ?array
    {
        if (!count($rows)) {
            return null;
        }

        $values = [];
        foreach ($rows as [$eventUid, $name, $value]) {
            $values[] = $eventUid;
            $values[] = $name;
            $values[] = $value;
        }

        $statement = sprintf(
            'INSERT INTO `%s` (`event_uid`, `name`, `value`) VALUES %s',
            $tableName,
            implode(', ', array_fill(0, count($rows), '(?, ?, ?)')),
        );

        return [$statement, $values];
    }

    case MYSQL = 'mysql';
    case SQLITE = 'sqlite';
}
