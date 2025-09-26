<?php

declare(strict_types=1);

namespace Backslash\Repository;

use Backslash\EventBus\EventBusInterface;
use Backslash\EventStore\EventStoreInterface;
use Backslash\EventStore\Query\QueryInterface;
use Backslash\Model\ModelInterface;
use RuntimeException;

final class Core implements RepositoryInterface
{
    private EventStoreInterface $eventStore;

    private EventBusInterface $eventBus;

    private array $cache = [];

    public function __construct(EventStoreInterface $eventStore, EventBusInterface $eventBus)
    {
        $this->eventStore = $eventStore;
        $this->eventBus = $eventBus;
    }

    public function loadModel(string $modelClass, ?QueryInterface $query): ModelInterface
    {
        $storedEvents = $this->eventStore->fetch($query);
        /** @var ModelInterface $model */
        $model = new $modelClass();
        $model->applyEvents($storedEvents);
        $this->cache[spl_object_id($model)] = [$query, $storedEvents->getHighestSequence()];
        return $model;
    }

    public function storeChanges(ModelInterface $model): void
    {
        if (!array_key_exists(spl_object_id($model), $this->cache)) {
            throw new RuntimeException();
        }
        [$query, $expectedSequence] = $this->cache[spl_object_id($model)];
        $newEvents = $model->getChanges();
        $model->clearChanges();
        $this->eventStore->append($newEvents, $query, $expectedSequence);
        $this->eventBus->publish($newEvents);
        unset($this->cache[spl_object_id($model)]);
    }
}
