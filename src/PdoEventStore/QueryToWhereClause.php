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

final class QueryToWhereClause
{
    private ?QueryInterface $query;

    private Driver $driver;

    private Config $config;

    private EventNameResolverInterface $eventNameResolver;

    private string $statement;

    private array $values = [];

    private bool $resolved = false;

    public function __construct(
        ?QueryInterface $query,
        Driver $driver,
        Config $config,
        EventNameResolverInterface $eventNameResolver,
    ) {
        $this->query = $query;
        $this->driver = $driver;
        $this->config = $config;
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
                    '%s %s (%s)',
                    sprintf('`%s`', $this->config->getAlias('event_name')),
                    $query->isNegative() ? 'NOT IN' : 'IN',
                    implode(', ', array_fill(0, count($eventNames), '?')),
                );
                $this->values = array_merge($this->values, $eventNames);
                break;
            case (Identifier::class):
                /** @var Identifier $query */
                if ($query->isIncludes()) {
                    $statement = $this->driver->buildJsonArrayIncludesStatement(
                        $this->config->getAlias('event_identifiers'),
                        $query->getName(),
                    );
                } else {
                    $statement = sprintf(
                        '%s %s (%s)',
                        $this->driver->buildJsonExtractStatement(
                            $this->config->getAlias('event_identifiers'),
                            $query->getName(),
                        ),
                        $query->isNegative() ? 'NOT IN' : 'IN',
                        implode(', ', array_fill(0, count($query->getValues()), '?')),
                    );
                }
                $this->values = array_merge($this->values, $query->getValues());
                break;
            case (Metadata::class):
                /** @var Metadata $query */
                $statement = sprintf(
                    '%s %s (%s)',
                    $this->driver->buildJsonExtractStatement(
                        $this->config->getAlias('event_metadata'),
                        $query->getName(),
                    ),
                    $query->isNegative() ? 'NOT IN' : 'IN',
                    implode(', ', array_fill(0, count($query->getValues()), '?')),
                );
                $this->values = array_merge($this->values, $query->getValues());
                break;
            case (Sequence::class):
                /** @var Sequence $query */
                $statement = sprintf(
                    '%s %s',
                    sprintf('`%s`', $this->config->getAlias('sequence')),
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
                    '%s %s ?',
                    sprintf('`%s`', $this->config->getAlias('event_time')),
                    $query->isAfter() ? '>=' : '<=',
                );
                $this->values = array_merge($this->values, [$query->getDateTime()->format('Y-m-d\TH:i:s.uP')]);
                break;
        }

        if (count($this->query->getSubqueries())) {
            /** @var LogicOperator $operator */
            /** @var QueryInterface $subquery */
            foreach ($this->query->getSubqueries() as [$operator, $subquery]) {
                $where = new self($subquery, $this->driver, $this->config, $this->eventNameResolver);
                $this->values = array_merge($this->values, $where->getValues());
                $statement .= sprintf(' %s (%s)', $operator->value, $where->getStatement());
            }
        }
        $this->statement = $statement;
    }
}
