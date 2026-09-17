<?php

declare(strict_types=1);

namespace Backslash\EventStore\Query;

final class Query
{
    /** @var QueryItem[] */
    private array $items;

    public function __construct(EventClass|Identifier|Metadata $filter, EventClass|Identifier|Metadata ...$filters)
    {
        $this->items = [new QueryItem($filter, ...$filters)];
    }

    public function or(EventClass|Identifier|Metadata $filter, EventClass|Identifier|Metadata ...$filters): self
    {
        $clone = clone $this;
        $clone->items = [...$this->items, new QueryItem($filter, ...$filters)];
        return $clone;
    }

    /** @return QueryItem[] */
    public function getItems(): array
    {
        return $this->items;
    }
}
