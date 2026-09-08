<?php

declare(strict_types=1);

namespace Backslash\EventStore\Query;

final class QueryItem
{
    /** @var string[] */
    private array $eventClasses = [];

    /** @var Identifier[] */
    private array $identifiers = [];

    /** @var Metadata[] */
    private array $metadata = [];

    public function __construct(EventClass|Identifier|Metadata $filter, EventClass|Identifier|Metadata ...$filters)
    {
        foreach ([$filter, ...$filters] as $eachFilter) {
            $this->add($eachFilter);
        }
    }

    public function with(EventClass|Identifier|Metadata $filter): self
    {
        $clone = clone $this;
        $clone->add($filter);
        return $clone;
    }

    /** @return string[] */
    public function getEventClasses(): array
    {
        return $this->eventClasses;
    }

    /** @return Identifier[] */
    public function getIdentifiers(): array
    {
        return $this->identifiers;
    }

    /** @return Metadata[] */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    private function add(EventClass|Identifier|Metadata $filter): void
    {
        match (true) {
            $filter instanceof EventClass => $this->eventClasses = [...$this->eventClasses, ...$filter->getValues()],
            $filter instanceof Identifier => $this->identifiers[] = $filter,
            $filter instanceof Metadata => $this->metadata[] = $filter,
        };
    }
}
