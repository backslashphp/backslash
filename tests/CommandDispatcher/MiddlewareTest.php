<?php

declare(strict_types=1);

namespace Backslash\CommandDispatcher;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MiddlewareTest extends TestCase
{
    #[Test]
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

    #[Test]
    public function it_adds_inner_middleware_closest_to_core(): void
    {
        $output = [];
        $outer = new TestMiddleware('outer', $output);
        $inner = new TestMiddleware('inner', $output);

        $bus = new Dispatcher();
        $bus->addMiddleware($outer);
        $bus->addInnerMiddleware($inner);

        $bus->dispatch(new TestCommand());

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
