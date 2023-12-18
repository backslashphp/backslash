<?php

declare(strict_types=1);

namespace Backslash\CacheProjectionStoreMiddleware;

use Backslash\ProjectionStore\ProjectionStore;
use PHPUnit\Framework\TestCase;

class CacheProjectionStoreMiddlewareTest extends TestCase
{
    /** @test */
    public function it_caches_projections(): void
    {
        $adapter = new TestProjectionStoreAdapter();
        $store = new ProjectionStore($adapter);
        $store->addMiddleware(new CacheProjectionStoreMiddleware());

        $store->store(new TestProjection('123'));
        $store->commit();
        $this->assertEquals(1, $adapter->getHits());

        $adapter->reset();
        $store->find('123', TestProjection::class);
        $store->find('123', TestProjection::class);
        $store->find('123', TestProjection::class);
        $this->assertEquals(0, $adapter->getHits());

        $store->store(new TestProjection('234'));
        $store->commit();
        $this->assertEquals(1, $adapter->getHits());

        $store->find('234', TestProjection::class);
        $this->assertEquals(1, $adapter->getHits());

        $store->has('345', TestProjection::class);
        $this->assertEquals(2, $adapter->getHits());
    }
}
