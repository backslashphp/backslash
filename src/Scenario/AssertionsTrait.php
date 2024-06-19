<?php

declare(strict_types=1);

namespace Backslash\Scenario;

use Backslash\Scenario\Constraint\ProjectionsMustContain;
use Backslash\Scenario\Constraint\ProjectionsMustContainExactly;
use Backslash\Scenario\Constraint\ProjectionsMustContainOnly;
use Backslash\Scenario\Constraint\ProjectionsMustCount;
use Backslash\Scenario\Constraint\StreamMustContain;
use Backslash\Scenario\Constraint\StreamMustContainExactly;
use Backslash\Scenario\Constraint\StreamMustContainOnly;
use Backslash\Scenario\Constraint\StreamMustCount;

trait AssertionsTrait
{
    public static function assertPublishedEventsContain(string $eventFqcn, PublishedEvents $publishedEvents): void
    {
        self::assertThat($publishedEvents, new StreamMustContain($eventFqcn));
    }

    public static function assertPublishedEventsContainOnly(string $eventFqcn, PublishedEvents $publishedEvents): void
    {
        self::assertThat($publishedEvents, new StreamMustContainOnly($eventFqcn));
    }

    public static function assertPublishedEventsContainExactly(
        array $eventFqcnAndCount,
        PublishedEvents $publishedEvents,
    ): void {
        foreach ($eventFqcnAndCount as $fqcn => $count) {
            self::assertThat($publishedEvents, new StreamMustContainExactly($count, $fqcn));
        }
    }

    public static function assertPublishedEventsDoNotContain(
        string $eventClass,
        PublishedEvents $publishedEvents,
    ): void {
        self::assertPublishedEventsContainExactly([$eventClass => 0], $publishedEvents);
    }

    public static function assertPublishedEventsCount(int $count, PublishedEvents $publishedEvents): void
    {
        self::assertThat($publishedEvents, new StreamMustCount($count));
    }

    public static function assertUpdatedProjectionsContain(
        string $projectionFqcn,
        UpdatedProjections $updatedProjections,
    ): void {
        self::assertThat($updatedProjections, new ProjectionsMustContain($projectionFqcn));
    }

    public static function assertUpdatedProjectionsContainOnly(
        string $projectionFqcn,
        UpdatedProjections $updatedProjections,
    ): void {
        self::assertThat($updatedProjections, new ProjectionsMustContainOnly($projectionFqcn));
    }

    public static function assertUpdatedProjectionsContainExactly(
        array $projectionFqcnAndCount,
        UpdatedProjections $updatedProjections,
    ): void {
        foreach ($projectionFqcnAndCount as $fqcn => $count) {
            self::assertThat($updatedProjections, new ProjectionsMustContainExactly($count, $fqcn));
        }
    }

    public static function assertUpdatedProjectionsDoNotContain(
        string $projectionFqcn,
        UpdatedProjections $updatedProjections,
    ): void {
        self::assertUpdatedProjectionsContainExactly([$projectionFqcn => 0], $updatedProjections);
    }

    public static function assertUpdatedProjectionsCount(int $count, UpdatedProjections $updatedProjections): void
    {
        self::assertThat($updatedProjections, new ProjectionsMustCount($count));
    }
}
