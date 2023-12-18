<?php

declare(strict_types=1);

namespace Backslash\Pdo;

use PDO;
use PDOStatement;

interface PdoInterface
{
    public static function getAvailableDrivers(): array;

    public function getPdo(): PDO;

    public function beginTransaction(): bool;

    public function commit(): bool;

    public function errorCode(): ?string;

    public function errorInfo(): array;

    public function exec(string $statement): int|bool;

    public function getAttribute(int $attribute): mixed;

    public function inTransaction(): bool;

    public function lastInsertId(?string $name = null): string;

    public function prepare(string $statement, array $driverOptions = []): PDOStatement|bool;

    public function query(
        string $statement,
        ?int $mode = PDO::ATTR_DEFAULT_FETCH_MODE,
        ?string $className = null,
        ?array $constructorArgs = [],
    ): PDOStatement|bool;

    public function quote(string $string, int $parameterType = PDO::PARAM_STR): string|bool;

    public function rollBack(): bool;

    public function setAttribute(int $attribute, mixed $value): bool;
}
