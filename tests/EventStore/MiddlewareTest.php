<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Clock\Clock;
use Backslash\Domain\Metadata;
use Backslash\Domain\RecordedEvent;
use Backslash\Domain\RecordedEventStream;
use Backslash\Shared\Event\StudentNameChangedEvent;
use Backslash\Shared\Event\StudentRegisteredEvent;
use Backslash\Shared\EventStore\TestAdapter;
use PHPUnit\Framework\TestCase;

class MiddlewareTest extends TestCase
{
    /** @test */
    public function it_executes_middlewares_in_lifo_order(): void
    {
        $store = new EventStore(new TestAdapter());
        $stream = new RecordedEventStream(
            RecordedEvent::create(new StudentRegisteredEvent('1', 'John'), new Metadata(), Clock::now()),
        );
        $store->append($stream, null, null);

        $output = [];
        $store->addMiddleware(new TestMiddleware('mw1', $output));
        $store->addMiddleware(new TestMiddleware('mw2', $output));
        $store->addMiddleware(new TestMiddleware('mw3', $output));

        $store->fetch(null);
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

        $stream = new RecordedEventStream(
            RecordedEvent::create(new StudentNameChangedEvent('1', 'John', 'James'), new Metadata(), Clock::now()),
        );
        $store->append($stream, null, null);
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

        $store->inspect(new TestInspector());
        $this->assertEquals(
            $output,
            [
                'before inspect mw3',
                'before inspect mw2',
                'before inspect mw1',
                'after inspect mw1',
                'after inspect mw2',
                'after inspect mw3',
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
