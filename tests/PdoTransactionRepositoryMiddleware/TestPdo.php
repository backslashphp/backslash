<?php

declare(strict_types=1);

namespace Backslash\PdoTransactionRepositoryMiddleware;

use Backslash\Pdo\PdoInterface;
use PDO;
use PDOStatement;
use RuntimeException;

class TestPdo implements PdoInterface
{
    private bool $inTransaction = false;

    private array $calls = [];

    public function __construct(
        private readonly bool $mysql = false,
        private readonly int|null $getLockResult = 1,
    ) {
    }

    public static function getAvailableDrivers(): array
    {
        return [];
    }

    public function getPdo(): PDO
    {
        throw new RuntimeException('Not implemented');
    }

    public function beginTransaction(): bool
    {
        $this->calls[] = 'beginTransaction';
        $this->inTransaction = true;
        return true;
    }

    public function commit(): bool
    {
        $this->calls[] = 'commit';
        $this->inTransaction = false;
        return true;
    }

    public function rollBack(): bool
    {
        $this->calls[] = 'rollBack';
        $this->inTransaction = false;
        return true;
    }

    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }

    public function errorCode(): ?string
    {
        return null;
    }

    public function errorInfo(): array
    {
        return [];
    }

    public function exec(string $statement): int|bool
    {
        return false;
    }

    public function getAttribute(int $attribute): mixed
    {
        if ($attribute === PDO::ATTR_DRIVER_NAME) {
            return $this->mysql ? 'mysql' : null;
        }
        return null;
    }

    public function lastInsertId(?string $name = null): string
    {
        return '0';
    }

    public function prepare(string $statement, array $driverOptions = []): PDOStatement|bool
    {
        if (str_contains($statement, 'GET_LOCK')) {
            return new TestLockStatement(
                fn (?array $params) => $this->calls[] = 'GET_LOCK(' . $params[0] . ')',
                $this->getLockResult,
            );
        }
        if (str_contains($statement, 'RELEASE_LOCK')) {
            return new TestLockStatement(
                fn (?array $params) => $this->calls[] = 'RELEASE_LOCK(' . $params[0] . ')',
            );
        }
        return false;
    }

    public function query(
        string $statement,
        ?int $mode = PDO::ATTR_DEFAULT_FETCH_MODE,
        ?string $className = null,
        ?array $constructorArgs = [],
    ): PDOStatement|bool {
        return false;
    }

    public function quote(string $string, int $parameterType = PDO::PARAM_STR): string|bool
    {
        return false;
    }

    public function setAttribute(int $attribute, mixed $value): bool
    {
        return false;
    }

    public function getCalls(): array
    {
        return $this->calls;
    }
}
