<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Event\RecordedEvent;
use Backslash\EventStore\Query\Query;

final class CallableInspector implements InspectorInterface
{
    /** @var callable */
    private $callable;

    private ?Query $query;

    public function __construct(callable $callable, ?Query $query)
    {
        $this->callable = $callable;
        $this->query = $query;
    }

    public function getQuery(): ?Query
    {
        return $this->query;
    }

    public function inspect(RecordedEvent $recordedEvent): void
    {
        $callable = $this->callable;
        $callable($recordedEvent);
    }
}
