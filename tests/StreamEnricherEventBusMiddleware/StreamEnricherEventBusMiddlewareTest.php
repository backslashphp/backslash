<?php

declare(strict_types=1);

namespace Backslash\StreamEnricherEventBusMiddleware;

use Backslash\Clock\Clock;
use Backslash\Domain\Metadata;
use Backslash\Domain\RecordedEvent;
use Backslash\Domain\RecordedEventStream;
use Backslash\EventBus\EventBus;
use Backslash\EventBus\EventStreamPublisherInterface;
use Backslash\EventBus\MiddlewareInterface;
use Backslash\Shared\Event\StudentRegisteredEvent;
use Backslash\Shared\StreamEnricher\TestEnricher;
use Backslash\StreamEnricher\StreamEnricherEventBusMiddleware;
use PHPUnit\Framework\TestCase;

class StreamEnricherEventBusMiddlewareTest extends TestCase
{
    /** @test */
    public function it_enriches_stream(): void
    {
        $mw = new class () implements MiddlewareInterface {
            public ?Metadata $metadata = null;

            public function publish(RecordedEventStream $stream, EventStreamPublisherInterface $next): void
            {
                $this->metadata = $stream->getRecordedEvents()[0]->getMetadata();
                $next->publish($stream);
            }
        };

        $bus = new EventBus();
        $bus->addMiddleware($mw);
        $bus->addMiddleware(new StreamEnricherEventBusMiddleware(new TestEnricher()));

        $stream = new RecordedEventStream(
            RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), new Metadata(), Clock::now()),
        );

        $bus->publish($stream);

        $this->assertEquals($mw->metadata->toArray(), ['foo' => 'bar']);
    }
}
