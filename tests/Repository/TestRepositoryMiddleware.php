<?php

declare(strict_types=1);

namespace Backslash\Repository;

use Backslash\Domain\StateInterface;
use Backslash\EventStore\Query\QueryInterface;

class TestRepositoryMiddleware implements MiddlewareInterface
{
    private string $name;

    private array $output;

    public function __construct(string $name, array &$output)
    {
        $this->name = $name;
        $this->output = &$output;
    }

    public function load(string $class, QueryInterface $query, RepositoryInterface $next): StateInterface
    {
        $this->output[] = 'before ' . $this->name;
        $state = $next->load($class, $query);
        $this->output[] = 'after ' . $this->name;
        return $state;
    }

    public function store(StateInterface $state, RepositoryInterface $next): void
    {
        $this->output[] = 'before ' . $this->name;
        $next->store($state);
        $this->output[] = 'after ' . $this->name;
    }
}
