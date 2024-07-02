<?php

declare(strict_types=1);

namespace Backslash\EventStore\Query;

interface QueryInterface
{
    public function and(self $subquery): self;

    public function or(self $subquery): self;

    /** @return self[] */
    public function getSubqueries(): array;
}
