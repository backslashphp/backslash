<?php

declare(strict_types=1);

namespace Backslash\PdoEventStore;

use Backslash\EventNameResolver\EventNameResolverInterface;
use Backslash\EventStore\Query\EventClass;
use Backslash\EventStore\Query\EventTime;
use Backslash\EventStore\Query\Identifier;
use Backslash\EventStore\Query\LogicOperator;
use Backslash\EventStore\Query\Metadata;
use Backslash\EventStore\Query\QueryInterface;
use Backslash\EventStore\Query\Sequence;
use LogicException;

final class QueryToWhereClause
{
    private ?QueryInterface $query;

    private EventNameResolverInterface $eventNameResolver;

    private string $statement;

    private array $values = [];

    private bool $resolved = false;

    public function __construct(
        ?QueryInterface $query,
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

        if (!$this->query) {
            $this->statement = '1=1';
            return;
        }

        $query = $this->query;
        switch ($query::class) {
            case (EventClass::class):
                $eventNames = array_map(
                    fn ($item) => $this->eventNameResolver->resolveName((string) $item),
                    $query->getValues(),
                );
                /** @var EventClass $query */
                $statement = sprintf(
                    '`event_name` %s (%s)',
                    $query->isNegative() ? 'NOT IN' : 'IN',
                    implode(', ', array_fill(0, count($eventNames), '?')),
                );
                $this->values = array_merge($this->values, $eventNames);
                break;
            case (Identifier::class):
                /** @var Identifier $query */
                $statement = $this->buildChildTableSubquery(
                    'event_store_identifiers',
                    $query->getName(),
                    $query->getValues(),
                    $query->isNegative(),
                );
                $this->values = array_merge($this->values, [$query->getName()], $query->getValues());
                break;
            case (Metadata::class):
                /** @var Metadata $query */
                $statement = $this->buildChildTableSubquery(
                    'event_store_metadata',
                    $query->getName(),
                    $query->getValues(),
                    $query->isNegative(),
                );
                $this->values = array_merge($this->values, [$query->getName()], $query->getValues());
                break;
            case (Sequence::class):
                /** @var Sequence $query */
                $statement = sprintf(
                    '`sequence` %s',
                    match (true) {
                        $query->getMin() && $query->getMax() => sprintf('BETWEEN %d AND %d', $query->getMin(), $query->getMax()),
                        $query->getMin() && !$query->getMax() => sprintf('>= %d', $query->getMin()),
                        !$query->getMin() && $query->getMax() => sprintf('<= %d', $query->getMax()),
                    },
                );
                break;
            case (EventTime::class):
                /** @var EventTime $query */
                $statement = sprintf(
                    '`event_time` %s ?',
                    $query->isAfter() ? '>=' : '<=',
                );
                $this->values = array_merge($this->values, [$query->getDateTime()->format('Y-m-d\TH:i:s.uP')]);
                break;
            default:
                throw new LogicException(sprintf('Unsupported query type: %s', $query::class));
        }

        if (count($this->query->getSubqueries())) {
            /** @var LogicOperator $operator */
            /** @var QueryInterface $subquery */
            foreach ($this->query->getSubqueries() as [$operator, $subquery]) {
                $where = new self($subquery, $this->eventNameResolver);
                $this->values = array_merge($this->values, $where->getValues());
                $statement .= sprintf(' %s (%s)', $operator->value, $where->getStatement());
            }
        }
        $this->statement = $statement;
    }

    private function buildChildTableSubquery(string $tableName, string $name, array $values, bool $negative): string
    {
        return sprintf(
            '`event_store`.`sequence` %s (SELECT `sequence` FROM `%s` WHERE `name` = ? AND `value` IN (%s))',
            $negative ? 'NOT IN' : 'IN',
            $tableName,
            implode(', ', array_fill(0, count($values), '?')),
        );
    }
}
