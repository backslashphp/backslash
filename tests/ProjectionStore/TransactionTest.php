<?php

declare(strict_types=1);

namespace Backslash\ProjectionStore;

use Backslash\Shared\Projection\TestFooProjection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    #[Test]
    public function it_commits_and_rollbacks(): void
    {
        $store = new ProjectionStore(new InMemoryProjectionStoreAdapter());
        $this->assertFalse($store->has('123', TestFooProjection::class));

        $store->store(new TestFooProjection('123'));
        $this->assertTrue($store->has('123', TestFooProjection::class));

        $store->rollback();
        $this->assertFalse($store->has('123', TestFooProjection::class));

        $store->store(new TestFooProjection('123'));
        $this->assertTrue($store->has('123', TestFooProjection::class));

        $store->commit();
        $this->assertTrue($store->has('123', TestFooProjection::class));
    }

    #[Test]
    public function it_resets_unit_of_work_after_commit(): void
    {
        $adapter = new TestInMemoryProjectionStoreAdapter();
        $store = new ProjectionStore($adapter);

        $store->store(new TestFooProjection('123'));
        $store->remove('234', TestFooProjection::class);
        $store->commit();
        $this->assertCount(1, $adapter->getCommitedUnitOfWork()->getStored());
        $this->assertCount(1, $adapter->getCommitedUnitOfWork()->getRemoved());

        $store->commit();
        $this->assertCount(0, $adapter->getCommitedUnitOfWork()->getStored());
        $this->assertCount(0, $adapter->getCommitedUnitOfWork()->getRemoved());
    }
}
