<?php

declare(strict_types=1);

namespace Backslash\PdoEventStore;

use OutOfBoundsException;

final class Config
{
    private string $table = 'event_store';

    private array $aliases = [
        'sequence' => 'sequence',
        'event_uid' => 'event_uid',
        'event_name' => 'event_name',
        'event_identifiers' => 'event_identifiers',
        'event_payload' => 'event_payload',
        'event_metadata' => 'event_metadata',
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
