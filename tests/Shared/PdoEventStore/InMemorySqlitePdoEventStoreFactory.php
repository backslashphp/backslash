<?php

declare(strict_types=1);

namespace Backslash\Shared\PdoEventStore;

use Backslash\Pdo\PdoProxy;
use Backslash\PdoEventStore\Config;
use Backslash\PdoEventStore\JsonEventSerializer;
use Backslash\PdoEventStore\JsonIdentifiersSerializer;
use Backslash\PdoEventStore\JsonMetadataSerializer;
use Backslash\PdoEventStore\PdoEventStoreAdapter;
use PDO;
use Ramsey\Uuid\Uuid;

class InMemorySqlitePdoEventStoreFactory
{
    public static function build(): PdoEventStoreAdapter
    {
        $pdo = new PdoProxy(fn () => new PDO('sqlite::memory:'));
        $adapter = new PdoEventStoreAdapter(
            $pdo,
            new Config(),
            new JsonEventSerializer(),
            new JsonIdentifiersSerializer(),
            new JsonMetadataSerializer(),
            fn () => Uuid::uuid4()->toString(),
        );
        $adapter->setupDatabase();
        return $adapter;
    }
}
