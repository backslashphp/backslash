<?php

declare(strict_types=1);

namespace Backslash\PdoTransactionRepositoryMiddleware;

use PDOStatement;

class TestLockStatement extends PDOStatement
{
    /** @param callable $onExecute */
    public function __construct(
        private $onExecute,
        private readonly int|null $fetchColumnResult = null,
    ) {
    }

    public function execute(?array $params = null): bool
    {
        ($this->onExecute)($params);
        return true;
    }

    public function fetchColumn(int $column = 0): mixed
    {
        return $this->fetchColumnResult;
    }
}
