<?php

declare(strict_types=1);

namespace Backslash\PdoTransactionRepositoryMiddleware;

use Backslash\EventStore\Query\QueryInterface;
use Backslash\Model\ModelInterface;
use Backslash\Pdo\PdoInterface;
use Backslash\Repository\MiddlewareInterface;
use Backslash\Repository\RepositoryInterface;
use RuntimeException;
use Throwable;

final class PdoTransactionRepositoryMiddleware implements MiddlewareInterface
{
    private PdoInterface $pdo;

    private int $nestedLevels;

    public function __construct(PdoInterface $pdo)
    {
        $this->pdo = $pdo;
        $this->nestedLevels = 0;
    }

    public function loadModel(string $modelClass, ?QueryInterface $query, RepositoryInterface $next): ModelInterface
    {
        return $next->loadModel($modelClass, $query);
    }

    public function storeChanges(ModelInterface $model, RepositoryInterface $next): void
    {
        $this->nestedLevels++;
        if ($this->nestedLevels === 1) {
            $this->startTransaction();
        }
        try {
            $next->storeChanges($model);
        } catch (Throwable $t) {
            $this->nestedLevels = 0;
            $this->rollbackTransaction();
            throw $t;
        }
        $this->nestedLevels--;
        if ($this->nestedLevels === 0) {
            $this->commitTransaction();
        }
    }

    private function startTransaction(): void
    {
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
        } else {
            throw new RuntimeException('Transaction already started.');
        }
    }

    private function rollbackTransaction(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    private function commitTransaction(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->commit();
        } else {
            throw new RuntimeException('Not in transaction.');
        }
    }
}
