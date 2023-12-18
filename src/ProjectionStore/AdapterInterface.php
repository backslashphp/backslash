<?php

declare(strict_types=1);

namespace Backslash\ProjectionStore;

use Backslash\Projection\ProjectionInterface;
use Generator;

interface AdapterInterface
{
    /** @throws ProjectionNotFoundException */
    public function find(string $id, string $class): ProjectionInterface;

    public function findBy(string $class): Generator;

    public function has(string $id, string $class): bool;

    public function commit(UnitOfWork $unit): void;

    public function purge(): void;
}
