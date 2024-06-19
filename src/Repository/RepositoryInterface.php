<?php

declare(strict_types=1);

namespace Backslash\Repository;

use Backslash\Domain\StateInterface;
use Backslash\EventStore\Query\QueryInterface;

interface RepositoryInterface
{
    public function load(string $class, QueryInterface $query): StateInterface;

    public function store(StateInterface $state): void;
}
