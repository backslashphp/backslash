<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Domain\RecordedEventStream;
use Backslash\EventStore\Query\QueryInterface;

final class EventStore implements EventStoreInterface
{
    private AdapterInterface $adapter;

    /** @var MiddlewareInterface[] */
    private array $middlewares;

    private EventStoreInterface $chain;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $this->middlewares = [];
        $this->chainMiddlewares();
    }

    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
        $this->chainMiddlewares();
    }

    /** @return MiddlewareInterface[] */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function fetch(?QueryInterface $query, int $fromSequence = 0): StoredRecordedEventStream
    {
        return $this->chain->fetch($query, $fromSequence);
    }

    /** @throws ConcurrencyException */
    public function append(RecordedEventStream $stream, ?QueryInterface $concurrencyCheck, ?int $expectedSequence): void
    {
        $this->chain->append($stream, $concurrencyCheck, $expectedSequence);
    }

    public function inspect(InspectorInterface $inspector): void
    {
        $this->chain->inspect($inspector);
    }

    public function purge(): void
    {
        $this->chain->purge();
    }

    private function chainMiddlewares(): void
    {
        $this->chain = array_reduce(
            $this->middlewares,
            fn (EventStoreInterface $carry, MiddlewareInterface $item): EventStoreInterface => new MiddlewareDelegator(
                $item,
                $carry,
            ),
            $this->adapter,
        );
    }
}
