<?php

declare(strict_types=1);

namespace Backslash\Repository;

use Backslash\EventStore\Query\Query;
use Backslash\Model\ModelInterface;

interface MiddlewareInterface
{
    public function loadModel(string $modelClass, ?Query $query, RepositoryInterface $next): ModelInterface;

    public function storeChanges(ModelInterface $model, RepositoryInterface $next): void;
}
