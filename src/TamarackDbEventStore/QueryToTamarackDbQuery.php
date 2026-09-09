<?php

declare(strict_types=1);

namespace Backslash\TamarackDbEventStore;

use Backslash\EventNameResolver\EventNameResolverInterface;
use Backslash\EventStore\Query\Query;
use Backslash\EventStore\Query\QueryItem;

final class QueryToTamarackDbQuery
{
    private Query $query;

    private EventNameResolverInterface $eventNameResolver;

    public function __construct(Query $query, EventNameResolverInterface $eventNameResolver)
    {
        $this->query = $query;
        $this->eventNameResolver = $eventNameResolver;
    }

    /** @return "*"|array<array{types?: string[], identifiers?: array, metadata?: array}> */
    public function toPayload(): array|string
    {
        if ($this->query->isMatchAll()) {
            return '*';
        }

        return array_map(
            fn (QueryItem $item) => $this->buildItemPayload($item),
            $this->query->getItems(),
        );
    }

    private function buildItemPayload(QueryItem $item): array
    {
        $payload = [];

        if (count($item->getEventClasses())) {
            $payload['types'] = array_map(
                fn ($eventClass) => $this->eventNameResolver->resolveName($eventClass),
                $item->getEventClasses(),
            );
        }

        if (count($item->getIdentifiers())) {
            $payload['identifiers'] = array_map(
                fn ($identifier) => ['name' => $identifier->getName(), 'value' => $identifier->getValue()],
                $item->getIdentifiers(),
            );
        }

        if (count($item->getMetadata())) {
            $payload['metadata'] = array_map(
                fn ($metadata) => ['name' => $metadata->getName(), 'value' => $metadata->getValue()],
                $item->getMetadata(),
            );
        }

        return $payload;
    }
}
