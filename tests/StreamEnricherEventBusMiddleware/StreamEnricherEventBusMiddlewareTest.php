<?php

declare(strict_types=1);

namespace Backslash\StreamEnricherEventBusMiddleware;

use Backslash\Aggregate\Metadata;
use Backslash\Aggregate\RecordedEvent;
use Backslash\Aggregate\Stream;
use Backslash\EventBus\EventBus;
use Backslash\EventBus\EventStreamPublisherInterface;
use Backslash\EventBus\MiddlewareInterface;
use Backslash\StreamEnricher\StreamEnricherEventBusMiddleware;
use PHPUnit\Framework\TestCase;

class StreamEnricherEventBusMiddlewareTest extends TestCase
{
    /** @test */
    public function it_enriches_stream(): void
    {
        $mw = new class () implements MiddlewareInterface {
            public ?Metadata $metadata = null;

            public function publish(Stream $stream, EventStreamPublisherInterface $next): void
            {
                $this->metadata = $stream->getRecordedEvents()[0]->getMetadata();
                $next->publish($stream);
            }
        };

        $bus = new EventBus();
        $bus->addMiddleware($mw);
        $bus->addMiddleware(new StreamEnricherEventBusMiddleware(new TestEnricher()));

        $stream = (new Stream('123', 'type'))
            ->withRecordedEvent(RecordedEvent::createNow(new TestEvent(), new Metadata(), 1));

        $bus->publish($stream);

        $this->assertEquals($mw->metadata->toArray(), ['foo' => 'bar']);
    }
}
