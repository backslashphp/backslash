<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Aggregate\Stream;

class TestMiddleware implements MiddlewareInterface
{
    private string $name;

    private array $output;

    public function __construct(string $name, array &$output)
    {
        $this->name = $name;
        $this->output = &$output;
    }

    public function fetch(string $aggregateId, string $aggregateType, int $fromVersion, EventStoreInterface $next): Stream
    {
        $this->output[] = 'before fetch ' . $this->name;
        $stream = $next->fetch($aggregateId, $aggregateType, $fromVersion);
        $this->output[] = 'after fetch ' . $this->name;
        return $stream;
    }

    public function streamExists(string $aggregateId, string $aggregateType, EventStoreInterface $next): bool
    {
        $this->output[] = 'before streamExists ' . $this->name;
        $exists = $next->streamExists($aggregateId, $aggregateType);
        $this->output[] = 'after streamExists ' . $this->name;
        return $exists;
    }

    public function getVersion(string $aggregateId, string $aggregateType, EventStoreInterface $next): int
    {
        $this->output[] = 'before getVersion ' . $this->name;
        $version = $next->getVersion($aggregateId, $aggregateType);
        $this->output[] = 'after getVersion ' . $this->name;
        return $version;
    }

    public function append(Stream $stream, ?int $expectedVersion, EventStoreInterface $next): void
    {
        $this->output[] = 'before append ' . $this->name;
        $next->append($stream, $expectedVersion);
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
