<?php

declare(strict_types=1);

namespace Backslash\Scenario;

use Backslash\Scenario\Constraint\UpdatedProjectionsMustContain;
use Backslash\Scenario\Constraint\UpdatedProjectionsMustContainExactly;
use Backslash\Scenario\Constraint\UpdatedProjectionsMustContainOnly;
use Backslash\Scenario\Constraint\UpdatedProjectionsMustCount;
use Backslash\Scenario\Constraint\PublishedEventsMustContain;
use Backslash\Scenario\Constraint\PublishedEventsMustContainExactly;
use Backslash\Scenario\Constraint\PublishedEventsMustContainOnly;
use Backslash\Scenario\Constraint\PublishedEventsMustCount;

trait AssertionsTrait
{
    public static function assertPublishedEventsContain(string $eventFqcn, PublishedEvents $publishedEvents): void
    {
        self::assertThat($publishedEvents, new PublishedEventsMustContain($eventFqcn));
    }

    public static function assertPublishedEventsContainOnly(string $eventFqcn, PublishedEvents $publishedEvents): void
    {
        self::assertThat($publishedEvents, new PublishedEventsMustContainOnly($eventFqcn));
    }

    public static function assertPublishedEventsContainExactly(
        array $eventFqcnAndCount,
        PublishedEvents $publishedEvents,
    ): void {
        foreach ($eventFqcnAndCount as $fqcn => $count) {
            self::assertThat($publishedEvents, new PublishedEventsMustContainExactly($count, $fqcn));
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
        self::assertThat($publishedEvents, new PublishedEventsMustCount($count));
    }

    public static function assertUpdatedProjectionsContain(
        string $projectionFqcn,
        UpdatedProjections $updatedProjections,
    ): void {
        self::assertThat($updatedProjections, new UpdatedProjectionsMustContain($projectionFqcn));
    }

    public static function assertUpdatedProjectionsContainOnly(
        string $projectionFqcn,
        UpdatedProjections $updatedProjections,
    ): void {
        self::assertThat($updatedProjections, new UpdatedProjectionsMustContainOnly($projectionFqcn));
    }

    public static function assertUpdatedProjectionsContainExactly(
        array $projectionFqcnAndCount,
        UpdatedProjections $updatedProjections,
    ): void {
        foreach ($projectionFqcnAndCount as $fqcn => $count) {
            self::assertThat($updatedProjections, new UpdatedProjectionsMustContainExactly($count, $fqcn));
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
        self::assertThat($updatedProjections, new UpdatedProjectionsMustCount($count));
    }
}
