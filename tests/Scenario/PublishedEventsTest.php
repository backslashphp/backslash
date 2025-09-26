<?php

declare(strict_types=1);

namespace Backslash\Scenario;

use Backslash\Clock\Clock;
use Backslash\Event\Metadata;
use Backslash\Event\RecordedEvent;
use Backslash\Event\RecordedEventStream;
use Backslash\Shared\Event\CourseCreatedEvent;
use Backslash\Shared\Event\StudentNameChangedEvent;
use Backslash\Shared\Event\StudentRegisteredEvent;
use PHPUnit\Framework\TestCase;

class PublishedEventsTest extends TestCase
{
    /** @test */
    public function it_counts_events(): void
    {
        $publishedEvents = new PublishedEvents($this->getRecordedEventStream());

        $this->assertCount(3, $publishedEvents);
    }

    /** @test */
    public function it_returns_events_of_type(): void
    {
        $publishedEvents = new PublishedEvents($this->getRecordedEventStream());

        $this->assertCount(2, $publishedEvents->getAllOf(StudentRegisteredEvent::class));
        $this->assertCount(1, $publishedEvents->getAllOf(CourseCreatedEvent::class));
        $this->assertCount(0, $publishedEvents->getAllOf(StudentNameChangedEvent::class));
    }

    /** @test */
    public function it_returns_all_events(): void
    {
        $publishedEvents = new PublishedEvents($this->getRecordedEventStream());

        $this->assertCount(3, $publishedEvents->getAll());
    }

    private function getRecordedEventStream(): RecordedEventStream
    {
        return new RecordedEventStream(
            RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), new Metadata(), Clock::now()),
            RecordedEvent::create(new StudentRegisteredEvent('2', 'Bill'), new Metadata(), Clock::now()),
            RecordedEvent::create(new CourseCreatedEvent('A', 'FR101', 'French 101'), new Metadata(), Clock::now()),
        );
    }
}
