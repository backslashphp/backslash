<?php

declare(strict_types=1);

namespace Backslash\Repository;

use Backslash\EventStore\Query\QueryInterface;
use Backslash\Model\ModelInterface;

interface RepositoryInterface
{
    public function loadModel(string $modelClass, ?QueryInterface $query): ModelInterface;

    public function storeChanges(ModelInterface $model): void;
}
