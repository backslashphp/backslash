<?php

declare(strict_types=1);

namespace Backslash\EventBus;

use Backslash\Aggregate\Stream;
use PHPUnit\Framework\TestCase;

class MiddlewareTest extends TestCase
{
    /** @test */
    public function it_executes_middlewares_in_lifo_order(): void
    {
        $bus = new EventBus();

        $output = [];
        $bus->addMiddleware(new TestMiddleware('mw1', $output));
        $bus->addMiddleware(new TestMiddleware('mw2', $output));
        $bus->addMiddleware(new TestMiddleware('mw3', $output));

        $bus->publish(new Stream('123', 'type'));
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
}
