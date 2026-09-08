<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Event\RecordedEventStream;
use Backslash\EventStore\Query\Query;

final class MiddlewareDelegator implements EventStoreInterface
{
    private MiddlewareInterface $middleware;

    private ?EventStoreInterface $next;

    public function __construct(MiddlewareInterface $middleware, ?EventStoreInterface $next = null)
    {
        $this->middleware = $middleware;
        $this->next = $next;
    }

    public function fetch(Query $query, int $fromSequence = 0): StoredRecordedEventStream
    {
        return $this->middleware->fetch($query, $fromSequence, $this->next);
    }

    public function append(RecordedEventStream $stream, Query $concurrencyCheck, ?int $expectedSequence): void
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
