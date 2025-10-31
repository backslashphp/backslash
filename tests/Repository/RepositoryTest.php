<?php

declare(strict_types=1);

namespace Backslash\Repository;

use Backslash\EventBus\EventBus;
use Backslash\EventNameResolver\MatchingClassEventNameResolverAdapter;
use Backslash\EventStore\EventStore;
use Backslash\EventStore\Query\EventClass;
use Backslash\Pdo\PdoProxy;
use Backslash\PdoEventStore\Config;
use Backslash\PdoEventStore\JsonEventSerializer;
use Backslash\PdoEventStore\JsonIdentifiersSerializer;
use Backslash\PdoEventStore\JsonMetadataSerializer;
use Backslash\PdoEventStore\PdoEventStoreAdapter;
use Backslash\Shared\Model\StudentNameChangeModel;
use Backslash\Shared\Model\StudentRegistrationModel;
use PDO;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class RepositoryTest extends TestCase
{
    private Repository $repository;

    private TestEventBusMiddleware $testEventBusMiddleware;

    public function setUp(): void
    {
        parent::setUp();

        $pdo = new PdoProxy(fn () => new PDO('sqlite::memory:'));
        $eventNameResolver = new MatchingClassEventNameResolverAdapter();
        $adapter = new PdoEventStoreAdapter(
            $pdo,
            new Config(),
            $eventNameResolver,
            new JsonEventSerializer($eventNameResolver),
            new JsonIdentifiersSerializer(),
            new JsonMetadataSerializer(),
            fn () => Uuid::uuid4()->toString(),
        );
        $adapter->setupDatabase();
        $this->testEventBusMiddleware = new TestEventBusMiddleware();
        $eventBus = new EventBus();
        $eventBus->addMiddleware($this->testEventBusMiddleware);
        $this->repository = new Repository(new EventStore($adapter), $eventBus);
    }

    /** @test */
    public function it_creates_stores_and_loads_model(): void
    {
        $studentId = Uuid::uuid4()->toString();

        $this->assertFalse($this->testEventBusMiddleware->wasCalled());

        /** @var StudentRegistrationModel $model */
        $model = $this->repository->loadModel(StudentRegistrationModel::class, StudentRegistrationModel::getQuery($studentId));
        $this->assertInstanceOf(StudentRegistrationModel::class, $model);
        $this->assertCount(0, $model->getChanges());

        $model->register($studentId, 'John Smith');
        $this->assertCount(1, $model->getChanges());
        $this->repository->storeChanges($model);

        /** @var StudentNameChangeModel $model */
        $model = $this->repository->loadModel(StudentNameChangeModel::class, StudentNameChangeModel::getQuery($studentId));
        $model->changeName('Jane Doe');
        $this->repository->storeChanges($model);

        $this->assertTrue($this->testEventBusMiddleware->wasCalled());
    }

    /** @test */
    public function it_executes_middlewares_in_lifo_order(): void
    {
        $output = [];
        $this->repository->addMiddleware(new TestRepositoryMiddleware('mw1', $output));
        $this->repository->addMiddleware(new TestRepositoryMiddleware('mw2', $output));
        $this->repository->addMiddleware(new TestRepositoryMiddleware('mw3', $output));

        $this->repository->loadModel(StudentRegistrationModel::class, EventClass::in());
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
}
