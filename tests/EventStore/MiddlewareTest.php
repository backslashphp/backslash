<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Aggregate\Metadata;
use Backslash\Aggregate\RecordedEvent;
use Backslash\Aggregate\Stream;
use PHPUnit\Framework\TestCase;

class MiddlewareTest extends TestCase
{
    /** @test */
    public function it_executes_middlewares_in_lifo_order(): void
    {
        $store = new EventStore(new InMemoryEventStoreAdapter());
        $stream = (new Stream('123', 'type'))
            ->withRecordedEvent(RecordedEvent::createNow(new TestEvent(), new Metadata(), 1));
        $store->append($stream);

        $output = [];
        $store->addMiddleware(new TestMiddleware('mw1', $output));
        $store->addMiddleware(new TestMiddleware('mw2', $output));
        $store->addMiddleware(new TestMiddleware('mw3', $output));

        $store->streamExists('123', 'type');
        $this->assertEquals(
            $output,
            [
                'before streamExists mw3',
                'before streamExists mw2',
                'before streamExists mw1',
                'after streamExists mw1',
                'after streamExists mw2',
                'after streamExists mw3',
            ],
        );
        $output = [];

        $store->fetch('123', 'type');
        $this->assertEquals(
            $output,
            [
                'before fetch mw3',
                'before fetch mw2',
                'before fetch mw1',
                'after fetch mw1',
                'after fetch mw2',
                'after fetch mw3',
            ],
        );
        $output = [];

        $store->getVersion('123', 'type');
        $this->assertEquals(
            $output,
            [
                'before getVersion mw3',
                'before getVersion mw2',
                'before getVersion mw1',
                'after getVersion mw1',
                'after getVersion mw2',
                'after getVersion mw3',
            ],
        );
        $output = [];

        $stream = (new Stream('123', 'type'))
            ->withRecordedEvent(RecordedEvent::createNow(new TestEvent(), new Metadata(), 2));
        $store->append($stream);
        $this->assertEquals(
            $output,
            [
                'before append mw3',
                'before append mw2',
                'before append mw1',
                'after append mw1',
                'after append mw2',
                'after append mw3',
            ],
        );
        $output = [];

        $store->purge();
        $this->assertEquals(
            $output,
            [
                'before purge mw3',
                'before purge mw2',
                'before purge mw1',
                'after purge mw1',
                'after purge mw2',
                'after purge mw3',
            ],
        );
    }
}
