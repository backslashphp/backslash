<?php

declare(strict_types=1);

namespace Backslash\ProjectionStore;

use PHPUnit\Framework\TestCase;

class MiddlewareTest extends TestCase
{
    /** @test */
    public function it_executes_middlewares_in_lifo_order(): void
    {
        $store = new ProjectionStore(new InMemoryProjectionStoreAdapter());

        $output = [];
        $store->addMiddleware(new TestMiddleware('mw1', $output));
        $store->addMiddleware(new TestMiddleware('mw2', $output));
        $store->addMiddleware(new TestMiddleware('mw3', $output));

        $store->store(new TestProjection('123'));
        $this->assertEquals(
            $output,
            [
                'before store mw3',
                'before store mw2',
                'before store mw1',
                'after store mw1',
                'after store mw2',
                'after store mw3',
            ],
        );
        $output = [];

        $store->has('123', TestProjection::class);
        $this->assertEquals(
            $output,
            [
                'before has mw3',
                'before has mw2',
                'before has mw1',
                'after has mw1',
                'after has mw2',
                'after has mw3',
            ],
        );
        $output = [];

        $store->find('123', TestProjection::class);
        $this->assertEquals(
            $output,
            [
                'before find mw3',
                'before find mw2',
                'before find mw1',
                'after find mw1',
                'after find mw2',
                'after find mw3',
            ],
        );
        $output = [];

        $store->remove('123', TestProjection::class);
        $this->assertEquals(
            $output,
            [
                'before remove mw3',
                'before remove mw2',
                'before remove mw1',
                'after remove mw1',
                'after remove mw2',
                'after remove mw3',
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
