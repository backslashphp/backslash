<?php

declare(strict_types=1);

namespace Backslash\PdoProjectionStore;

use Backslash\Serializer\Serializer;
use Backslash\Serializer\SerializeFunctionSerializer;
use Backslash\Pdo\PdoProxy;
use Backslash\ProjectionStore\ProjectionStore;
use Backslash\Shared\Projection\TestBarProjection;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PdoProjectionStoreAdapterTest extends TestCase
{
    #[Test]
    public function it_persists_with_pdo(): void
    {
        $store = $this->createStore();
        $store->store(new TestProjection('123'));
        $store->commit();

        $projection = $store->find('123', TestProjection::class);

        $this->assertEquals(new TestProjection('123'), $projection);
    }

    #[Test]
    public function it_removes_projections_by_class(): void
    {
        $store = $this->createStore();
        $store->store(new TestProjection('123'));
        $store->store(new TestProjection('234'));
        $store->store(new TestBarProjection('345'));
        $store->commit();

        $store->removeBy(TestProjection::class);

        $this->assertFalse($store->has('123', TestProjection::class));
        $this->assertFalse($store->has('234', TestProjection::class));
        $this->assertTrue($store->has('345', TestBarProjection::class));
    }

    private function createStore(): ProjectionStore
    {
        $pdo = new PdoProxy(
            function (): PDO {
                $pdo = new PDO('sqlite::memory:');
                $pdo->exec(
                    'CREATE TABLE projection_store (
                projection_id VARCHAR,
                projection_class VARCHAR,
                projection_payload BLOB
            )',
                );
                return $pdo;
            },
        );
        $serializer = new Serializer(new SerializeFunctionSerializer());

        return new ProjectionStore(new PdoProjectionStoreAdapter($pdo, $serializer));
    }
}
