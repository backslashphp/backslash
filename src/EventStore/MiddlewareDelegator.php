<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Domain\RecordedEventStream;
use Backslash\EventStore\Query\QueryInterface;

final class MiddlewareDelegator implements EventStoreInterface
{
    private MiddlewareInterface $middleware;

    private ?EventStoreInterface $next;

    public function __construct(MiddlewareInterface $middleware, ?EventStoreInterface $next = null)
    {
        $this->middleware = $middleware;
        $this->next = $next;
    }

    public function fetch(?QueryInterface $query, int $fromSequence = 0): StoredRecordedEventStream
    {
        return $this->middleware->fetch($query, $fromSequence, $this->next);
    }

    public function append(RecordedEventStream $stream, ?QueryInterface $concurrencyCheck, ?int $expectedSequence): void
    {
        $this->middleware->append($stream, $concurrencyCheck, $expectedSequence, $this->next);
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
