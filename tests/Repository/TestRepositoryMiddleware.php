<?php

declare(strict_types=1);

namespace Backslash\Repository;

use Backslash\EventStore\Query\QueryInterface;
use Backslash\Model\ModelInterface;

class TestRepositoryMiddleware implements MiddlewareInterface
{
    private string $name;

    private array $output;

    public function __construct(string $name, array &$output)
    {
        $this->name = $name;
        $this->output = &$output;
    }

    public function loadModel(string $modelClass, ?QueryInterface $query, RepositoryInterface $next): ModelInterface
    {
        $this->output[] = 'before ' . $this->name;
        $model = $next->loadModel($modelClass, $query);
        $this->output[] = 'after ' . $this->name;
        return $model;
    }

    public function storeChanges(ModelInterface $model, RepositoryInterface $next): void
    {
        $this->output[] = 'before ' . $this->name;
        $next->storeChanges($model);
        $this->output[] = 'after ' . $this->name;
    }
}
