<?php

declare(strict_types=1);

namespace Backslash\PdoEventStore;

use OutOfBoundsException;

final class Config
{
    private string $table = 'event_store';

    /** @var string[] */
    private array $aliases = [
        'sequence' => 'sequence',
        'aggregate_type' => 'aggregate_type',
        'aggregate_id' => 'aggregate_id',
        'aggregate_version' => 'aggregate_version',
        'event_id' => 'event_id',
        'event_class' => 'event_class',
        'event_metadata' => 'event_metadata',
        'event_payload' => 'event_payload',
        'event_time' => 'event_time',
    ];

    public function withTable(string $table): self
    {
        $clone = clone $this;
        $clone->table = $this->sanitizeSqlName($table);
        return $clone;
    }

    public function withAlias(string $column, string $alias): self
    {
        if (!array_key_exists($column, $this->aliases)) {
            throw new OutOfBoundsException();
        }
        $clone = clone $this;
        $clone->aliases[$column] = $this->sanitizeSqlName($alias);
        return $clone;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getAlias(string $column): string
    {
        if (!array_key_exists($column, $this->aliases)) {
            throw new OutOfBoundsException();
        }
        return $this->aliases[$column];
    }

    private function sanitizeSqlName(string $value): string
    {
        return preg_replace('/[^a-zA-Z_]*/', '', $value);
    }
}
