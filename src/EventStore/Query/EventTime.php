<?php

declare(strict_types=1);

namespace Backslash\EventStore\Query;

use DateTimeImmutable;

class EventTime implements QueryInterface
{
    use SubqueriesTrait;

    private DateTimeImmutable $time;

    private bool $after;

    private function __construct(DateTimeImmutable $time, bool $after)
    {
        $this->time = $time;
        $this->after = $after;
    }

    public static function upTo(DateTimeImmutable $time): QueryInterface
    {
        return new self($time, false);
    }

    public static function from(DateTimeImmutable $time): QueryInterface
    {
        return new self($time, true);
    }

    public function getDateTime(): DateTimeImmutable
    {
        return $this->time;
    }

    public function isAfter(): bool
    {
        return $this->after;
    }
}
