<?php

declare(strict_types=1);

namespace Backslash\Repository;

use Backslash\Domain\StateInterface;
use Backslash\EventStore\Query\QueryInterface;

interface MiddlewareInterface
{
    public function load(string $class, ?QueryInterface $query, RepositoryInterface $next): StateInterface;

    public function store(StateInterface $state, RepositoryInterface $next): void;
}
