<?php

declare(strict_types=1);

namespace Backslash\Repository;

use Backslash\EventStore\Query\QueryInterface;
use Backslash\Model\ModelInterface;

interface MiddlewareInterface
{
    public function loadModel(string $modelClass, ?QueryInterface $query, RepositoryInterface $next): ModelInterface;

    public function storeChanges(ModelInterface $model, RepositoryInterface $next): void;
}
