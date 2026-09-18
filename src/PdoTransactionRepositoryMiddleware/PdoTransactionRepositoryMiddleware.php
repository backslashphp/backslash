<?php

declare(strict_types=1);

namespace Backslash\PdoTransactionRepositoryMiddleware;

use Backslash\EventStore\Query\Query;
use Backslash\Model\ModelInterface;
use Backslash\Pdo\PdoInterface;
use Backslash\Repository\MiddlewareInterface;
use Backslash\Repository\RepositoryInterface;
use PDO;
use RuntimeException;
use Throwable;

final class PdoTransactionRepositoryMiddleware implements MiddlewareInterface
{
    private const LOCK_NAME = 'backslash';

    private PdoInterface $pdo;

    private int $nestedLevels;

    private ?bool $isMysql = null;

    public function __construct(PdoInterface $pdo)
    {
        $this->pdo = $pdo;
        $this->nestedLevels = 0;
    }

    public function loadModel(string $modelClass, ?Query $query, RepositoryInterface $next): ModelInterface
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
        if ($this->pdo->inTransaction()) {
            throw new RuntimeException('Transaction already started.');
        }
        if ($this->isMysql()) {
            $this->acquireLock();
        }
        $this->pdo->beginTransaction();
    }

    private function rollbackTransaction(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        if ($this->isMysql()) {
            $this->releaseLock();
        }
    }

    private function commitTransaction(): void
    {
        if (!$this->pdo->inTransaction()) {
            throw new RuntimeException('Not in transaction.');
        }
        $this->pdo->commit();
        if ($this->isMysql()) {
            $this->releaseLock();
        }
    }

    private function isMysql(): bool
    {
        if ($this->isMysql === null) {
            $this->isMysql = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
        }
        return $this->isMysql;
    }

    private function acquireLock(): void
    {
        $statement = $this->pdo->prepare('SELECT GET_LOCK(?, -1)');
        $statement->execute([self::LOCK_NAME]);
        if ((int) $statement->fetchColumn() !== 1) {
            throw new RuntimeException('Unable to acquire the "' . self::LOCK_NAME . '" lock.');
        }
    }

    private function releaseLock(): void
    {
        $statement = $this->pdo->prepare('SELECT RELEASE_LOCK(?)');
        $statement->execute([self::LOCK_NAME]);
    }
}
