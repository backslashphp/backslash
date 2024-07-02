<?php

declare(strict_types=1);

namespace Backslash\EventStore\Query;

class Sequence implements QueryInterface
{
    use SubqueriesTrait;

    private ?int $min;

    private ?int $max;

    public function __construct(?int $min, ?int $max)
    {
        $this->min = $min;
        $this->max = $max;
    }

    public static function upTo(int $sequence): QueryInterface
    {
        return new self(null, $sequence);
    }

    public static function from(int $sequence): QueryInterface
    {
        return new self($sequence, null);
    }

    public static function before(int $sequence): QueryInterface
    {
        return new self(null, $sequence - 1);
    }

    public static function after(int $sequence): QueryInterface
    {
        return new self($sequence + 1, null);
    }

    public static function between(int $min, int $max): QueryInterface
    {
        return new self($min, $max);
    }

    public function getMin(): ?int
    {
        return $this->min;
    }

    public function getMax(): ?int
    {
        return $this->max;
    }
}
