<?php

declare(strict_types=1);

namespace Backslash\Clock;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class ClockTest extends TestCase
{
    protected function tearDown(): void
    {
        // Reset clock to default state after each test
        Clock::setClock(new class () implements ClockInterface {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable();
            }
        });

        // Use reflection to reset the static clock to null
        $reflection = new \ReflectionClass(Clock::class);
        $property = $reflection->getProperty('clock');
        $property->setValue(null, null);
    }

    #[Test]
    public function it_returns_current_time_by_default(): void
    {
        $before = new DateTimeImmutable();
        $now = Clock::now();
        $after = new DateTimeImmutable();

        // Verify the returned time is between before and after (allowing for microseconds)
        $this->assertGreaterThanOrEqual($before->getTimestamp(), $now->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $now->getTimestamp());
    }

    #[Test]
    public function it_uses_custom_clock_when_set(): void
    {
        $fixedTime = new DateTimeImmutable('2024-01-15 10:30:00');
        $customClock = new class ($fixedTime) implements ClockInterface {
            public function __construct(private DateTimeImmutable $time)
            {
            }

            public function now(): DateTimeImmutable
            {
                return $this->time;
            }
        };

        Clock::setClock($customClock);

        $this->assertEquals($fixedTime, Clock::now());
        $this->assertEquals($fixedTime, Clock::now()); // Should return same time consistently
    }

    #[Test]
    public function it_can_change_custom_clock(): void
    {
        $firstTime = new DateTimeImmutable('2024-01-15 10:30:00');
        $secondTime = new DateTimeImmutable('2024-01-15 11:30:00');

        $firstClock = new class ($firstTime) implements ClockInterface {
            public function __construct(private DateTimeImmutable $time)
            {
            }

            public function now(): DateTimeImmutable
            {
                return $this->time;
            }
        };

        $secondClock = new class ($secondTime) implements ClockInterface {
            public function __construct(private DateTimeImmutable $time)
            {
            }

            public function now(): DateTimeImmutable
            {
                return $this->time;
            }
        };

        Clock::setClock($firstClock);
        $this->assertEquals($firstTime, Clock::now());

        Clock::setClock($secondClock);
        $this->assertEquals($secondTime, Clock::now());
    }

    #[Test]
    public function it_returns_immutable_datetime(): void
    {
        $now = Clock::now();

        $this->assertInstanceOf(DateTimeImmutable::class, $now);
    }

    #[Test]
    public function it_uses_custom_clock_that_advances_time(): void
    {
        $baseTime = new DateTimeImmutable('2024-01-15 10:30:00');

        $advancingClock = new class ($baseTime) implements ClockInterface {
            private int $calls = 0;

            public function __construct(private DateTimeImmutable $baseTime)
            {
            }

            public function now(): DateTimeImmutable
            {
                $offset = $this->calls++;
                return $this->baseTime->modify("+{$offset} seconds");
            }
        };

        Clock::setClock($advancingClock);

        $first = Clock::now();
        $second = Clock::now();
        $third = Clock::now();

        // Each call should advance by 1 second
        $this->assertEquals(0, $first->getTimestamp() - $baseTime->getTimestamp());
        $this->assertEquals(1, $second->getTimestamp() - $baseTime->getTimestamp());
        $this->assertEquals(2, $third->getTimestamp() - $baseTime->getTimestamp());
    }

    #[Test]
    public function it_maintains_custom_clock_across_multiple_calls(): void
    {
        $fixedTime = new DateTimeImmutable('2024-01-15 10:30:00');

        $trackingClock = new class ($fixedTime) implements ClockInterface {
            private int $callCount = 0;

            public function __construct(
                private DateTimeImmutable $time,
            ) {
            }

            public function now(): DateTimeImmutable
            {
                $this->callCount++;
                return $this->time;
            }

            public function getCallCount(): int
            {
                return $this->callCount;
            }
        };

        Clock::setClock($trackingClock);

        Clock::now();
        Clock::now();
        Clock::now();

        $this->assertEquals(3, $trackingClock->getCallCount());
    }
}
