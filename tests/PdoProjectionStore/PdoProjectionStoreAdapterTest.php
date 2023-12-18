<?php

declare(strict_types=1);

namespace Backslash\PdoProjectionStore;

use Backslash\Serializer\Serializer;
use Backslash\Serializer\SerializeFunctionSerializer;
use Backslash\Pdo\PdoProxy;
use Backslash\ProjectionStore\ProjectionStore;
use PDO;
use PHPUnit\Framework\TestCase;

class PdoProjectionStoreAdapterTest extends TestCase
{
    /** @test */
    public function it_persists_with_pdo(): void
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

        $store = new ProjectionStore(new PdoProjectionStoreAdapter($pdo, $serializer, new Config()));
        $store->store(new TestProjection('123'));
        $store->commit();

        $projection = $store->find('123', TestProjection::class);

        $this->assertEquals(new TestProjection('123'), $projection);
    }
}
