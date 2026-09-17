<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Event\RecordedEventStream;
use Backslash\EventStore\Query\Query;

class TestMiddleware implements MiddlewareInterface
{
    private string $name;

    private array $output;

    public function __construct(string $name, array &$output)
    {
        $this->name = $name;
        $this->output = &$output;
    }

    public function fetch(?Query $query, int $fromSequence, EventStoreInterface $next): StoredRecordedEventStream
    {
        $this->output[] = 'before fetch ' . $this->name;
        $stream = $next->fetch($query, $fromSequence);
        $this->output[] = 'after fetch ' . $this->name;
        return $stream;
    }

    public function append(RecordedEventStream $stream, ?Query $concurrencyCheck, ?int $expectedSequence, EventStoreInterface $next): void
    {
        $this->output[] = 'before append ' . $this->name;
        $next->append($stream, $concurrencyCheck, $expectedSequence);
        $this->output[] = 'after append ' . $this->name;
    }

    public function inspect(InspectorInterface $inspector, EventStoreInterface $next): void
    {
        $this->output[] = 'before inspect ' . $this->name;
        $next->inspect($inspector);
        $this->output[] = 'after inspect ' . $this->name;
    }

    public function purge(EventStoreInterface $next): void
    {
        $this->output[] = 'before purge ' . $this->name;
        $next->purge();
        $this->output[] = 'after purge ' . $this->name;
    }
}
