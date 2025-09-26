<?php

declare(strict_types=1);

namespace Backslash\EventStore;

use Backslash\Event\RecordedEvent;
use Backslash\EventStore\Query\QueryInterface;

class CallableInspector implements InspectorInterface
{
    /** @var callable */
    private $callable;

    private ?QueryInterface $query;

    public function __construct(callable $callable, ?QueryInterface $query)
    {
        $this->callable = $callable;
        $this->query = $query;
    }

    public function getQuery(): ?QueryInterface
    {
        return $this->query;
    }

    public function inspect(RecordedEvent $recordedEvent): void
    {
        $callable = $this->callable;
        $callable($recordedEvent);
    }
}
