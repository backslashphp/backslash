<?php

declare(strict_types=1);

namespace Backslash\Scenario;

use Backslash\CommandDispatcher\Dispatcher;
use Backslash\CommandDispatcher\DispatcherInterface;
use Backslash\EventBus\EventBus;
use Backslash\EventNameResolver\MatchingClassEventNameResolverAdapter;
use Backslash\EventStore\EventStore;
use Backslash\EventStore\EventStoreInterface;
use Backslash\Pdo\PdoProxy;
use Backslash\PdoEventStore\Config;
use Backslash\PdoEventStore\JsonEventSerializer;
use Backslash\PdoEventStore\JsonIdentifiersSerializer;
use Backslash\PdoEventStore\JsonMetadataSerializer;
use Backslash\PdoEventStore\PdoEventStoreAdapter;
use Backslash\ProjectionStore\InMemoryProjectionStoreAdapter;
use Backslash\ProjectionStore\ProjectionStore;
use Backslash\Repository\Repository;
use Backslash\Repository\RepositoryInterface;
use PDO;
use Ramsey\Uuid\Uuid;

final class Scenario
{
    private EventBus $eventBus;

    private DispatcherInterface $dispatcher;

    private EventStoreInterface $eventStore;

    private RepositoryInterface $repository;

    private ScenarioEventBusMiddleware $eventBusMiddleware;

    private ScenarioProjectionStoreMiddleware $projectionStoreMiddleware;

    private ProjectionStore $projectionStore;

    public function __construct(
        ?EventBus $eventBus = null,
        ?DispatcherInterface $dispatcher = null,
        ?ProjectionStore $projectionStore = null,
        ?EventStoreInterface $eventStore = null,
        ?RepositoryInterface $repository = null,
    ) {
        $this->eventBus = $eventBus ?? new EventBus();
        $this->dispatcher = $dispatcher ?? new Dispatcher();
        $eventNameResolver = new MatchingClassEventNameResolverAdapter();
        $this->eventStore = $eventStore ?? new EventStore(new PdoEventStoreAdapter(
            new PdoProxy(fn () => new PDO('sqlite::memory:')),
            new Config(),
            $eventNameResolver,
            new JsonEventSerializer($eventNameResolver),
            new JsonIdentifiersSerializer(),
            new JsonMetadataSerializer(),
            fn () => Uuid::uuid4()->toString(),
        ));
        $this->eventBusMiddleware = new ScenarioEventBusMiddleware();
        $this->eventBus->addMiddleware($this->eventBusMiddleware);
        $this->projectionStoreMiddleware = new ScenarioProjectionStoreMiddleware();
        $this->projectionStore = $projectionStore ?? new ProjectionStore(new InMemoryProjectionStoreAdapter());
        $this->projectionStore->addMiddleware($this->projectionStoreMiddleware);
        $this->repository = $repository ?? new Repository($this->eventStore, $this->eventBus);
    }

    public function play(Play ...$plays): void
    {
        foreach ($plays as $play) {
            $play->run(
                $this->eventBus,
                $this->eventBusMiddleware,
                $this->eventStore,
                $this->dispatcher,
                $this->projectionStoreMiddleware,
                $this->repository,
            );
        }
    }
}
