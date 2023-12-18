<?php

declare(strict_types=1);

namespace Backslash\Scenario;

use Backslash\Scenario\Constraint\ProjectionsMustContainExactly;
use Backslash\Scenario\Constraint\ProjectionsMustContainOnly;
use Backslash\Scenario\Constraint\ProjectionsMustCount;
use Backslash\Scenario\Constraint\ProjectionsMustNotContain;
use Backslash\Scenario\Constraint\StreamMustExist;
use Backslash\Scenario\Constraint\StreamsMustContain;
use Backslash\Scenario\Constraint\StreamsMustContainExactly;
use Backslash\Scenario\Constraint\StreamsMustCount;

trait AssertionsTrait
{
    public static function assertPublishedStreamWithId(string $aggregateId, PublishedStreams $publishedStreams): void
    {
        self::assertThat($publishedStreams, new StreamMustExist($aggregateId));
    }

    public static function assertPublishedStreamsDoNotContain(
        string $eventClass,
        PublishedStreams $publishedStreams,
    ): void {
        self::assertPublishedStreamsContainExactly([$eventClass => 0], $publishedStreams);
    }

    public static function assertPublishedStreamsContainExactly(
        array $eventsAndCount,
        PublishedStreams $publishedStreams,
    ): void {
        foreach ($eventsAndCount as $event => $count) {
            self::assertThat($publishedStreams, new StreamsMustContainExactly($count, $event));
        }
    }

    public static function assertPublishedStreamsContains(string $eventClass, PublishedStreams $publishedStreams): void
    {
        foreach ((array) $eventClass as $event) {
            self::assertThat($publishedStreams, new StreamsMustContain($event));
        }
    }

    public static function assertPublishedStreamsCount(int $count, PublishedStreams $publishedStreams): void
    {
        self::assertThat($publishedStreams, new StreamsMustCount($count));
    }

    public static function assertUpdatedProjectionsContainExactly(
        array $projectionsAndCount,
        UpdatedProjections $updatedProjections,
    ): void {
        foreach ($projectionsAndCount as $projection => $count) {
            self::assertThat($updatedProjections, new ProjectionsMustContainExactly($count, $projection));
        }
    }

    public static function assertUpdatedProjectionsDoNotContain(
        string $projectionClass,
        UpdatedProjections $updatedProjections,
    ): void {
        self::assertThat($updatedProjections, new ProjectionsMustNotContain($projectionClass));
    }

    public static function assertUpdatedProjectionsContainOnly(
        string $projectionClass,
        UpdatedProjections $updatedProjections,
    ): void {
        self::assertThat($updatedProjections, new ProjectionsMustContainOnly($projectionClass));
    }

    public static function assertUpdatedProjectionsCount(int $count, UpdatedProjections $updatedProjections): void
    {
        self::assertThat($updatedProjections, new ProjectionsMustCount($count));
    }
}
