<?php

declare(strict_types=1);

namespace Backslash\CommandDispatcher;

use PHPUnit\Framework\TestCase;

class MiddlewareTest extends TestCase
{
    /** @test */
    public function it_executes_middlewares_in_lifo_order(): void
    {
        $output = [];
        $mw1 = new TestMiddleware('mw1', $output);
        $mw2 = new TestMiddleware('mw2', $output);
        $mw3 = new TestMiddleware('mw3', $output);

        $bus = new Dispatcher();
        $bus->addMiddleware($mw1);
        $bus->addMiddleware($mw2);
        $bus->addMiddleware($mw3);

        $bus->dispatch(new TestCommand());

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
