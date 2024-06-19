<?php

declare(strict_types=1);

namespace Backslash\Repository;

use Backslash\Domain\StateInterface;
use Backslash\EventBus\EventBusInterface;
use Backslash\EventStore\EventStoreInterface;
use Backslash\EventStore\Query\QueryInterface;
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

    public function load(string $class, QueryInterface $query): StateInterface
    {
        $storedEvents = $this->eventStore->fetch($query);
        /** @var StateInterface $state */
        $state = new $class();
        $state->replayEvents($storedEvents);
        $this->cache[spl_object_id($state)] = [$query, $storedEvents->getHighestSequence()];
        return $state;
    }

    public function store(StateInterface $state): void
    {
        if (!array_key_exists(spl_object_id($state), $this->cache)) {
            throw new RuntimeException();
        }
        [$query, $expectedSequence] = $this->cache[spl_object_id($state)];
        $newEvents = $state->pullNewEvents();
        $this->eventStore->append($newEvents, $query, $expectedSequence);
        $this->eventBus->publish($newEvents);
        unset($this->cache[spl_object_id($state)]);
    }
}
