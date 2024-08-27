<?php

declare(strict_types=1);

namespace Backslash\PdoEventStore;

use Backslash\Domain\RecordedEvent;
use Backslash\Domain\RecordedEventStream;
use Backslash\EventStore\Query\QueryInterface;
use Backslash\Serializer\SerializerInterface;
use UnexpectedValueException;

enum Driver: string
{
    public function buildCreateTableStatement(Config $config): string
    {
        return match ($this) {
            self::MYSQL => sprintf(
                'CREATE TABLE IF NOT EXISTS `%s` (`%s` MEDIUMINT NOT NULL AUTO_INCREMENT, `%s` TEXT NOT NULL, `%s` varchar(255) NOT NULL, `%s` JSON NOT NULL CHECK (JSON_VALID(`%s`)), `%s` JSON NOT NULL CHECK (JSON_VALID(`%s`)), `%s` JSON NOT NULL CHECK (JSON_VALID(`%s`)), `%s` varchar(255) NOT NULL, PRIMARY KEY (`%s`), CONSTRAINT `event_uid_unique` UNIQUE KEY (`%s`))',
                $config->getTable(),
                $config->getAlias('sequence'),
                $config->getAlias('event_uid'),
                $config->getAlias('event_class'),
                $config->getAlias('event_payload'),
                $config->getAlias('event_payload'),
                $config->getAlias('event_identifiers'),
                $config->getAlias('event_identifiers'),
                $config->getAlias('event_metadata'),
                $config->getAlias('event_metadata'),
                $config->getAlias('event_time'),
                $config->getAlias('sequence'),
                $config->getAlias('event_uid'),
            ),
            self::SQLITE => sprintf(
                'CREATE TABLE IF NOT EXISTS `%s` (`%s` INTEGER PRIMARY KEY AUTOINCREMENT, `%s` TEXT NOT NULL, `%s` TEXT NOT NULL, `%s` TEXT NOT NULL, `%s` TEXT NOT NULL, `%s` TEXT NOT NULL, `%s` TEXT, UNIQUE(`%s`))',
                $config->getTable(),
                $config->getAlias('sequence'),
                $config->getAlias('event_uid'),
                $config->getAlias('event_class'),
                $config->getAlias('event_payload'),
                $config->getAlias('event_identifiers'),
                $config->getAlias('event_metadata'),
                $config->getAlias('event_time'),
                $config->getAlias('event_uid'),
            ),
        };
    }

    public function buildTruncateTableStatement(Config $config): string
    {
        return match ($this) {
            self::MYSQL => sprintf('DELETE FROM `%s`', $config->getTable()),
            self::SQLITE => sprintf('DELETE FROM `%s`', $config->getTable()),
        };
    }

    public function buildSelectStatement(int $fromSequence, QueryToWhereClause $where, Config $config): string
    {
        return match ($this) {
            self::MYSQL => sprintf(
                'SELECT DISTINCT `%s`.* FROM `%s` WHERE `%s` >= %d AND %s ORDER BY `%s` ASC',
                $config->getTable(),
                $config->getTable(),
                $config->getAlias('sequence'),
                $fromSequence,
                $where->getStatement(),
                $config->getAlias('sequence'),
            ),
            self::SQLITE => sprintf(
                'SELECT DISTINCT `%s`.* FROM `%s` LEFT JOIN JSON_EACH(`%s`) ON JSON_VALID(`%s`) WHERE `%s` >= %d AND %s ORDER BY `%s` ASC',
                $config->getTable(),
                $config->getTable(),
                $config->getAlias('event_identifiers'),
                $config->getAlias('event_identifiers'),
                $config->getAlias('sequence'),
                $fromSequence,
                $where->getStatement(),
                $config->getAlias('sequence'),
            ),
        };
    }

    public function buildJsonExtractStatement(string $column, string $field): string
    {
        return match ($this) {
            self::MYSQL => sprintf('IFNULL(JSON_VALUE(`%s`, "$.%s"), "")', $column, $field),
            self::SQLITE => sprintf('IFNULL(JSON_EXTRACT(`%s`, "$.%s"), "")', $column, $field),
        };
    }

    public function buildJsonArrayIncludesStatement(string $column, string $path): string
    {
        if (preg_match('/[^a-zA-Z0-9_]/', $path) !== 0) {
            throw new UnexpectedValueException('$path must contain only letters, numbers or underscores.');
        }
        return match ($this) {
            self::MYSQL => sprintf('JSON_SEARCH(`%s`, "one", ?, "", "$.%s") IS NOT NULL', $column, $path),
            self::SQLITE => sprintf('EXISTS (SELECT 1 FROM JSON_EACH(`%s`, "$.%s") WHERE `value` = ?)', $column, $path),
        };
    }

    public function buildInsertStatementAndValues(
        RecordedEventStream $stream,
        ?QueryInterface $concurrencyCheck,
        ?int $expectedSequence,
        SerializerInterface $eventSerializer,
        SerializerInterface $identifiersSerializer,
        SerializerInterface $metadataSerializer,
        callable $eventIdGenerator,
        Config $config,
    ): array {
        $values = [];

        $unionSelects = [];
        /** @var RecordedEvent $recordedEvent */
        foreach ($stream as $index => $recordedEvent) {
            $unionSelects[] = sprintf('SELECT %d `union_index`, ? `col1`, ? `col2`, ? `col3`, ? `col4`, ? `col5`, ? `col6`', $index);
            $values = array_merge($values, [
                $eventIdGenerator(),
                $recordedEvent->getEvent()::class,
                $eventSerializer->serialize($recordedEvent->getEvent()),
                $identifiersSerializer->serialize($recordedEvent->getEvent()->getIdentifiers()),
                $metadataSerializer->serialize($recordedEvent->getMetadata()),
                $recordedEvent->getRecordTime()->format('Y-m-d\TH:i:s.uP'),
            ]);
        }
        $unionSelects = implode(' UNION ', $unionSelects) . ' ORDER BY `union_index` ASC';

        $concurrencyCheckWhere = new QueryToWhereClause($concurrencyCheck, $this, $config);
        $values = array_merge($values, $concurrencyCheckWhere->getValues());

        $statement = match ($this) {
            self::MYSQL => sprintf(
                'INSERT INTO `%s` (`%s`, `%s`, `%s`, `%s`, `%s`, `%s`) SELECT `col1`, `col2`, `col3`, `col4`, `col5`, `col6` FROM (%s) `union_selects` WHERE (SELECT IFNULL(MAX(`%s`), 0) FROM `%s` WHERE 1=1 AND %s) %s',
                $config->getTable(),
                $config->getAlias('event_uid'),
                $config->getAlias('event_class'),
                $config->getAlias('event_payload'),
                $config->getAlias('event_identifiers'),
                $config->getAlias('event_metadata'),
                $config->getAlias('event_time'),
                $unionSelects,
                $config->getAlias('sequence'),
                $config->getTable(),
                $concurrencyCheckWhere->getStatement(),
                $expectedSequence ? sprintf('= %d', $expectedSequence) : '>= 0',
            ),
            self::SQLITE => sprintf(
                'INSERT INTO `%s` (`%s`, `%s`, `%s`, `%s`, `%s`, `%s`) SELECT `col1`, `col2`, `col3`, `col4`, `col5`, `col6` FROM (%s) WHERE (SELECT IFNULL(MAX(`%s`), 0) FROM `%s` LEFT JOIN JSON_EACH(`%s`) ON JSON_VALID(`%s`) WHERE 1=1 AND %s) %s',
                $config->getTable(),
                $config->getAlias('event_uid'),
                $config->getAlias('event_class'),
                $config->getAlias('event_payload'),
                $config->getAlias('event_identifiers'),
                $config->getAlias('event_metadata'),
                $config->getAlias('event_time'),
                $unionSelects,
                $config->getAlias('sequence'),
                $config->getTable(),
                $config->getAlias('event_identifiers'),
                $config->getAlias('event_identifiers'),
                $concurrencyCheckWhere->getStatement(),
                $expectedSequence ? sprintf('= %d', $expectedSequence) : '>= 0',
            ),
        };

        return [$statement, $values];
    }

    case MYSQL = 'mysql';
    case SQLITE = 'sqlite';
}
