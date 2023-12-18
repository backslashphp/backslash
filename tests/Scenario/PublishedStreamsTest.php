<?php

declare(strict_types=1);

namespace Backslash\Scenario;

use Backslash\Aggregate\Metadata;
use Backslash\Aggregate\RecordedEvent;
use Backslash\Aggregate\Stream;
use PHPUnit\Framework\TestCase;

class PublishedStreamsTest extends TestCase
{
    /** @test */
    public function it_counts_streams(): void
    {
        $publishedStreams = new PublishedStreams($this->getStreams());

        $this->assertCount(2, $publishedStreams);
    }

    /** @test */
    public function it_returns_events_of_type(): void
    {
        $publishedStreams = new PublishedStreams($this->getStreams());

        $this->assertCount(4, $publishedStreams->getAllOf(TestEvent1::class));
        $this->assertCount(3, $publishedStreams->getAllOf(TestEvent2::class));
    }

    /** @test */
    public function it_returns_all_streams(): void
    {
        $publishedStreams = new PublishedStreams($this->getStreams());

        $this->assertCount(2, $publishedStreams->getAll());
    }

    private function getStreams(): array
    {
        return [
            (new Stream('123', 'type'))
                ->withRecordedEvent(RecordedEvent::createNow(new TestEvent1(), new Metadata(), 1))
                ->withRecordedEvent(RecordedEvent::createNow(new TestEvent2(), new Metadata(), 2))
                ->withRecordedEvent(RecordedEvent::createNow(new TestEvent1(), new Metadata(), 3))
                ->withRecordedEvent(RecordedEvent::createNow(new TestEvent1(), new Metadata(), 4)),
            (new Stream('234', 'type'))
                ->withRecordedEvent(RecordedEvent::createNow(new TestEvent2(), new Metadata(), 1))
                ->withRecordedEvent(RecordedEvent::createNow(new TestEvent1(), new Metadata(), 2))
                ->withRecordedEvent(RecordedEvent::createNow(new TestEvent2(), new Metadata(), 3)),
        ];
    }
}
