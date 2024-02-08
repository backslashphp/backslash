<?php

declare(strict_types=1);

namespace Backslash\Pdo;

use PDO;
use PDOStatement;
use RuntimeException;

class PdoProxy implements PdoInterface
{
    /** @var callable */
    private $resolver;

    private bool $resolved = false;

    private ?PDO $pdo = null;

    /**
     * @param callable $resolver A callable that returns an instance of PDO
     */
    public function __construct(callable $resolver)
    {
        $this->resolver = $resolver;
    }

    public static function getAvailableDrivers(): array
    {
        return call_user_func_array('PDO::getAvailableDrivers', func_get_args());
    }

    public function beginTransaction(): bool
    {
        return call_user_func_array([$this->getPdo(), 'beginTransaction'], func_get_args());
    }

    public function getPdo(): PDO
    {
        if ($this->resolved) {
            return $this->pdo;
        }
        $resolver = $this->resolver;
        $object = $resolver();
        if (!$object instanceof PDO) {
            throw new RuntimeException('The callable did not return a PDO instance.');
        }
        $this->pdo = $object;
        $this->resolved = true;
        return $this->pdo;
    }

    public function commit(): bool
    {
        return call_user_func_array([$this->getPdo(), 'commit'], func_get_args());
    }

    public function errorCode(): ?string
    {
        return call_user_func_array([$this->getPdo(), 'errorCode'], func_get_args());
    }

    public function errorInfo(): array
    {
        return call_user_func_array([$this->getPdo(), 'errorInfo'], func_get_args());
    }

    public function exec(string $statement): bool|int
    {
        return (int) call_user_func_array([$this->getPdo(), 'exec'], func_get_args());
    }

    public function getAttribute(int $attribute): mixed
    {
        return call_user_func_array([$this->getPdo(), 'getAttribute'], func_get_args());
    }

    public function inTransaction(): bool
    {
        return call_user_func_array([$this->getPdo(), 'inTransaction'], func_get_args());
    }

    public function lastInsertId(?string $name = null): string
    {
        return call_user_func_array([$this->getPdo(), 'lastInsertId'], func_get_args());
    }

    public function prepare(string $statement, array $driverOptions = []): PDOStatement|bool
    {
        return call_user_func_array([$this->getPdo(), 'prepare'], func_get_args());
    }

    public function query(
        string $statement,
        ?int $mode = PDO::ATTR_DEFAULT_FETCH_MODE,
        ?string $className = null,
        ?array $constructorArgs = [],
    ): PDOStatement|bool {
        return call_user_func_array([$this->getPdo(), 'query'], func_get_args());
    }

    public function quote(string $string, int $parameterType = PDO::PARAM_STR): string|bool
    {
        return call_user_func_array([$this->getPdo(), 'quote'], func_get_args());
    }

    public function rollback(): bool
    {
        return call_user_func_array([$this->getPdo(), 'rollback'], func_get_args());
    }

    public function setAttribute(int $attribute, mixed $value): bool
    {
        return call_user_func_array([$this->getPdo(), 'setAttribute'], func_get_args());
    }
}
