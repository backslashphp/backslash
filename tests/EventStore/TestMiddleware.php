<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Domain\RecordedEventStream;
use Backslash\EventStore\Query\QueryInterface;

class TestMiddleware implements MiddlewareInterface
{
    private string $name;

    private array $output;

    public function __construct(string $name, array &$output)
    {
        $this->name = $name;
        $this->output = &$output;
    }

    public function fetch(?QueryInterface $query, int $fromSequence, EventStoreInterface $next): StoredRecordedEventStream
    {
        $this->output[] = 'before fetch ' . $this->name;
        $stream = $next->fetch($query, $fromSequence);
        $this->output[] = 'after fetch ' . $this->name;
        return $stream;
    }

    public function append(RecordedEventStream $stream, ?QueryInterface $concurrencyCheck, ?int $expectedSequence, EventStoreInterface $next): void
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
