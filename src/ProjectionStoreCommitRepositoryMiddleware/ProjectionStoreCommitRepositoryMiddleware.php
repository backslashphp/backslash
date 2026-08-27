<?php

declare(strict_types=1);

namespace Backslash\ProjectionStoreCommitRepositoryMiddleware;

use Backslash\EventStore\Query\QueryInterface;
use Backslash\Model\ModelInterface;
use Backslash\ProjectionStore\ProjectionStoreInterface;
use Backslash\Repository\MiddlewareInterface;
use Backslash\Repository\RepositoryInterface;
use Throwable;

final class ProjectionStoreCommitRepositoryMiddleware implements MiddlewareInterface
{
    private ProjectionStoreInterface $projections;

    private int $nestedLevels;

    public function __construct(ProjectionStoreInterface $projections)
    {
        $this->projections = $projections;
        $this->nestedLevels = 0;
    }

    public function loadModel(string $modelClass, ?QueryInterface $query, RepositoryInterface $next): ModelInterface
    {
        return $next->loadModel($modelClass, $query);
    }

    public function storeChanges(ModelInterface $model, RepositoryInterface $next): void
    {
        $this->nestedLevels++;
        try {
            $next->storeChanges($model);
        } catch (Throwable $t) {
            $this->nestedLevels = 0;
            $this->projections->rollback();
            throw $t;
        }
        $this->nestedLevels--;
        if ($this->nestedLevels === 0) {
            $this->projections->commit();
        }
    }
}
