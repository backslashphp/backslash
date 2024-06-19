<?php

declare(strict_types=1);

namespace Backslash\PdoEventStore;

use Backslash\EventStore\Query\EventClass;
use Backslash\EventStore\Query\Identifier;
use Backslash\EventStore\Query\LogicOperator;
use Backslash\EventStore\Query\QueryInterface;

final class QueryToWhereClause
{
    private ?QueryInterface $query;

    private Driver $driver;

    private Config $config;

    private string $statement;

    private array $values = [];

    private bool $resolved = false;

    public function __construct(?QueryInterface $query, Driver $driver, Config $config)
    {
        $this->query = $query;
        $this->driver = $driver;
        $this->config = $config;
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

        $this->values = array_merge($this->values, $this->query->getValues());

        $field = match ($this->query::class) {
            EventClass::class => sprintf('`%s`', $this->config->getAlias('event_class')),
            Identifier::class => $this->driver->buildJsonExtractStatement(
                $this->config->getAlias('event_identifiers'),
                $this->query->getName(),
            ),
        };
        $statement = sprintf(
            '%s %s (%s)',
            $field,
            $this->query->isNegative() ? 'NOT IN' : 'IN',
            implode(', ', array_fill(0, count($this->query->getValues()), '?')),
        );
        if (count($this->query->getSubqueries())) {
            /** @var LogicOperator $operator */
            /** @var QueryInterface $subquery */
            foreach ($this->query->getSubqueries() as [$operator, $subquery]) {
                $where = new self($subquery, $this->driver, $this->config);
                $this->values = array_merge($this->values, $where->getValues());
                $statement .= sprintf(' %s (%s)', $operator->value, $where->getStatement());
            }
        }
        $this->statement = $statement;
    }
}
