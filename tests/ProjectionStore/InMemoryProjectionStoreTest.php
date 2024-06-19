<?php

declare(strict_types=1);

namespace Backslash\ProjectionStore;

use Backslash\Shared\Projection\TestFooProjection;
use PHPUnit\Framework\TestCase;

class InMemoryProjectionStoreTest extends TestCase
{
    /** @test */
    public function it_throws_exception_when_projection_not_found(): void
    {
        $this->expectException(ProjectionNotFoundException::class);

        $adapter = new InMemoryProjectionStoreAdapter();
        $adapter->find('123', TestFooProjection::class);
    }

    /** @test */
    public function it_finds_projections_by_class(): void
    {
        $store = new ProjectionStore(new InMemoryProjectionStoreAdapter());

        $this->assertCount(0, iterator_to_array($store->getAdapter()->findBy(TestFooProjection::class)));

        $store->store(new TestFooProjection('123'));
        $store->store(new TestFooProjection('234'));
        $store->store(new TestFooProjection('345'));
        $store->commit();

        $this->assertCount(3, iterator_to_array($store->getAdapter()->findBy(TestFooProjection::class)));
    }
}
