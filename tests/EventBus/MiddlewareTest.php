<?php

declare(strict_types=1);

namespace Backslash\EventBus;

use Backslash\Event\RecordedEventStream;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MiddlewareTest extends TestCase
{
    #[Test]
    public function it_executes_middlewares_in_lifo_order(): void
    {
        $bus = new EventBus();

        $output = [];
        $bus->addMiddleware(new TestMiddleware('mw1', $output));
        $bus->addMiddleware(new TestMiddleware('mw2', $output));
        $bus->addMiddleware(new TestMiddleware('mw3', $output));

        $bus->publish(new RecordedEventStream());
        $this->assertEquals(
            $output,
            [
                'before mw3',
                'before mw2',
                'before mw1',
                'after mw1',
                'after mw2',
                'after mw3',
            ],
        );
    }

    #[Test]
    public function it_adds_inner_middleware_closest_to_core(): void
    {
        $bus = new EventBus();

        $output = [];
        $bus->addMiddleware(new TestMiddleware('outer', $output));
        $bus->addInnerMiddleware(new TestMiddleware('inner', $output));

        $bus->publish(new RecordedEventStream());
        $this->assertEquals(
            [
                'before outer',
                'before inner',
                'after inner',
                'after outer',
            ],
            $output,
        );
    }
}
