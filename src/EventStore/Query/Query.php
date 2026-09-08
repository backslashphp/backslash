<?php

declare(strict_types=1);

namespace Backslash\EventStore\Query;

final class Query
{
    /** @var QueryItem[] */
    private array $items;

    public function __construct(QueryItem ...$items)
    {
        $this->items = $items;
    }

    public function withItem(EventClass|Identifier|Metadata $filter, EventClass|Identifier|Metadata ...$filters): self
    {
        return new self(...[...$this->items, new QueryItem($filter, ...$filters)]);
    }

    public function isMatchAll(): bool
    {
        return count($this->items) === 0;
    }

    /** @return QueryItem[] */
    public function getItems(): array
    {
        return $this->items;
    }
}
