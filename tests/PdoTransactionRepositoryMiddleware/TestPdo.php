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
        return null;
    }

    public function lastInsertId(?string $name = null): string
    {
        return '0';
    }

    public function prepare(string $statement, array $driverOptions = []): PDOStatement|bool
    {
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
