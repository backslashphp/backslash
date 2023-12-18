<?php

declare(strict_types=1);

namespace Backslash\Aggregate;

class TestEvent implements EventInterface
{
    use ToArrayTrait;

    private string $string;

    private int $integer;

    private array $array;

    private bool $bool;

    public function __construct(string $string, int $integer, array $array, bool $bool = false)
    {
        $this->string = $string;
        $this->integer = $integer;
        $this->array = $array;
        $this->bool = $bool;
    }

    public function getString(): string
    {
        return $this->string;
    }

    public function getInteger(): int
    {
        return $this->integer;
    }

    public function getArray(): array
    {
        return $this->array;
    }

    public function getBool(): bool
    {
        return $this->bool;
    }
}
