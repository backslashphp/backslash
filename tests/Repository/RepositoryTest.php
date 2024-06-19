<?php

declare(strict_types=1);

namespace Backslash\Repository;

use Backslash\EventBus\EventBus;
use Backslash\EventStore\EventStore;
use Backslash\EventStore\Query\EventClass;
use Backslash\Pdo\PdoProxy;
use Backslash\PdoEventStore\Config;
use Backslash\PdoEventStore\JsonEventSerializer;
use Backslash\PdoEventStore\JsonIdentifiersSerializer;
use Backslash\PdoEventStore\JsonMetadataSerializer;
use Backslash\PdoEventStore\PdoEventStoreAdapter;
use Backslash\Shared\State\StudentNameState;
use Backslash\Shared\State\StudentRegistrationState;
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
        $adapter = new PdoEventStoreAdapter(
            $pdo,
            new Config(),
            new JsonEventSerializer(),
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
    public function it_creates_stores_and_loads_state(): void
    {
        $studentId = Uuid::uuid4()->toString();

        $this->assertFalse($this->testEventBusMiddleware->wasCalled());

        /** @var StudentRegistrationState $state */
        $state = $this->repository->load(StudentRegistrationState::class, StudentRegistrationState::getQuery($studentId));
        $this->assertInstanceOf(StudentRegistrationState::class, $state);
        $this->assertCount(0, $state->peekNewEvents());

        $state->subscribe($studentId, 'John Smith');
        $this->assertCount(1, $state->peekNewEvents());
        $this->repository->store($state);

        /** @var StudentNameState $state */
        $state = $this->repository->load(StudentNameState::class, StudentNameState::getQuery($studentId));
        $state->changeName('Jane Doe');
        $this->repository->store($state);

        $this->assertTrue($this->testEventBusMiddleware->wasCalled());
    }

    /** @test */
    public function it_executes_middlewares_in_lifo_order(): void
    {
        $output = [];
        $this->repository->addMiddleware(new TestRepositoryMiddleware('mw1', $output));
        $this->repository->addMiddleware(new TestRepositoryMiddleware('mw2', $output));
        $this->repository->addMiddleware(new TestRepositoryMiddleware('mw3', $output));

        $this->repository->load(StudentRegistrationState::class, EventClass::in());
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
