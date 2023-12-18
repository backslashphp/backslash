<?php

declare(strict_types=1);

namespace Backslash\ProjectionStore;

use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    /** @test */
    public function it_commits_and_rollbacks(): void
    {
        $store = new ProjectionStore(new InMemoryProjectionStoreAdapter());
        $this->assertFalse($store->has('123', TestProjection::class));

        $store->store(new TestProjection('123'));
        $this->assertTrue($store->has('123', TestProjection::class));

        $store->rollback();
        $this->assertFalse($store->has('123', TestProjection::class));

        $store->store(new TestProjection('123'));
        $this->assertTrue($store->has('123', TestProjection::class));

        $store->commit();
        $this->assertTrue($store->has('123', TestProjection::class));
    }

    /** @test */
    public function it_resets_unit_of_work_after_commit(): void
    {
        $adapter = new TestInMemoryProjectionStoreAdapter();
        $store = new ProjectionStore($adapter);

        $store->store(new TestProjection('123'));
        $store->remove('234', TestProjection::class);
        $store->commit();
        $this->assertCount(1, $adapter->getCommitedUnitOfWork()->getStored());
        $this->assertCount(1, $adapter->getCommitedUnitOfWork()->getRemoved());

        $store->commit();
        $this->assertCount(0, $adapter->getCommitedUnitOfWork()->getStored());
        $this->assertCount(0, $adapter->getCommitedUnitOfWork()->getRemoved());
    }
}
