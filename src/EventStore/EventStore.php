<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Aggregate\Stream;

final class EventStore implements EventStoreInterface
{
    private AdapterInterface $adapter;

    /** @var MiddlewareInterface[] */
    private array $middlewares = [];

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

    public function fetch(string $aggregateId, string $aggregateType, int $fromVersion = 0): Stream
    {
        return $this->chain->fetch($aggregateId, $aggregateType, $fromVersion);
    }

    public function streamExists(string $aggregateId, string $aggregateType): bool
    {
        return $this->chain->streamExists($aggregateId, $aggregateType);
    }

    public function getVersion(string $aggregateId, string $aggregateType): int
    {
        return $this->chain->getVersion($aggregateId, $aggregateType);
    }

    public function append(Stream $stream, ?int $expectedVersion = null): void
    {
        $this->chain->append($stream, $expectedVersion);
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
