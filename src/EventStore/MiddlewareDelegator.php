<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Aggregate\Stream;

final class MiddlewareDelegator implements EventStoreInterface
{
    private MiddlewareInterface $middleware;

    private ?EventStoreInterface $next;

    public function __construct(MiddlewareInterface $middleware, ?EventStoreInterface $next = null)
    {
        $this->middleware = $middleware;
        $this->next = $next;
    }

    public function fetch(string $aggregateId, string $aggregateType, int $fromVersion = 0): Stream
    {
        return $this->middleware->fetch($aggregateId, $aggregateType, $fromVersion, $this->next);
    }

    public function streamExists(string $aggregateId, string $aggregateType): bool
    {
        return $this->middleware->streamExists($aggregateId, $aggregateType, $this->next);
    }

    public function getVersion(string $aggregateId, string $aggregateType): int
    {
        return $this->middleware->getVersion($aggregateId, $aggregateType, $this->next);
    }

    public function append(Stream $stream, ?int $expectedVersion = null): void
    {
        $this->middleware->append($stream, $expectedVersion, $this->next);
    }

    public function inspect(InspectorInterface $inspector): void
    {
        $this->middleware->inspect($inspector, $this->next);
    }

    public function purge(): void
    {
        $this->middleware->purge($this->next);
    }
}
