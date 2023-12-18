<?php

declare(strict_types=1);

namespace Backslash\AggregateStore;

use PHPUnit\Framework\TestCase;

class MiddlewareTest extends TestCase
{
    /** @test */
    public function it_executes_middlewares_in_lifo_order(): void
    {
        $repository = new AggregateStore(new InMemoryAggregateStoreAdapter());
        $repository->store(new TestAggregate('123'));

        $output = [];
        $repository->addMiddleware(new TestMiddleware('mw1', $output));
        $repository->addMiddleware(new TestMiddleware('mw2', $output));
        $repository->addMiddleware(new TestMiddleware('mw3', $output));

        $repository->has('123', TestAggregate::getType());
        $this->assertEquals(
            $output,
            [
                'before exists mw3',
                'before exists mw2',
                'before exists mw1',
                'after exists mw1',
                'after exists mw2',
                'after exists mw3',
            ],
        );
        $output = [];

        $aggregate = $repository->find('123', TestAggregate::getType());
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

        $repository->store($aggregate);
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

        $repository->remove('123', TestAggregate::getType());
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

        $repository->purge();
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
