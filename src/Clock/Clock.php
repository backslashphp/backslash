<?php

declare(strict_types=1);

namespace Backslash\Clock;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

final class Clock
{
    private static ?ClockInterface $clock = null;

    public static function setClock(ClockInterface $clock): void
    {
        self::$clock = $clock;
    }

    public static function now(): DateTimeImmutable
    {
        return self::$clock ? self::$clock->now() : new DateTimeImmutable();
    }
}
