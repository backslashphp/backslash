<?php

declare(strict_types=1);

namespace Backslash\PdoEventStore;

use Backslash\EventNameResolver\EventNameResolverInterface;
use Backslash\EventStore\Query\Query;

final class QueryToWhereClause
{
    private Query $query;

    private EventNameResolverInterface $eventNameResolver;

    private string $statement;

    private array $values = [];

    private bool $resolved = false;

    public function __construct(
        Query $query,
        EventNameResolverInterface $eventNameResolver,
    ) {
        $this->query = $query;
        $this->eventNameResolver = $eventNameResolver;
    }

    public function getStatement(): string
    {
        if (!$this->resolved) {
            $this->resolve();
        }
        return $this->statement;
    }

    public function getValues(): array
    {
        if (!$this->resolved) {
            $this->resolve();
        }
        return $this->values;
    }

    private function resolve(): void
    {
        $this->resolved = true;

        if ($this->query->isMatchAll()) {
            $this->statement = '1=1';
            return;
        }

        $itemStatements = [];
        foreach ($this->query->getItems() as $item) {
            $conditions = [];

            if (count($item->getEventClasses())) {
                $eventNames = array_map(
                    fn ($eventClass) => $this->eventNameResolver->resolveName($eventClass),
                    $item->getEventClasses(),
                );
                $conditions[] = sprintf(
                    '`event_name` IN (%s)',
                    implode(', ', array_fill(0, count($eventNames), '?')),
                );
                $this->values = array_merge($this->values, $eventNames);
            }

            foreach ($item->getIdentifiers() as $identifier) {
                $conditions[] = $this->buildChildTableSubquery('event_store_identifiers');
                $this->values = array_merge($this->values, [$identifier->getName(), $identifier->getValue()]);
            }

            foreach ($item->getMetadata() as $metadata) {
                $conditions[] = $this->buildChildTableSubquery('event_store_metadata');
                $this->values = array_merge($this->values, [$metadata->getName(), $metadata->getValue()]);
            }

            $itemStatements[] = count($conditions) ? implode(' AND ', $conditions) : '1=1';
        }

        $this->statement = implode(' OR ', array_map(fn ($statement) => sprintf('(%s)', $statement), $itemStatements));
    }

    private function buildChildTableSubquery(string $tableName): string
    {
        return sprintf(
            '`event_store`.`sequence` IN (SELECT `sequence` FROM `%s` WHERE `name` = ? AND `value` = ?)',
            $tableName,
        );
    }
}
