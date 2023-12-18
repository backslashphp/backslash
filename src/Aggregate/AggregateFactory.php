<?php

declare(strict_types=1);

namespace Backslash\Aggregate;

use RuntimeException;

/**
 * AggregateFactory rebuilds an aggregate from an event stream.
 */
final class AggregateFactory
{
    private string $aggregateRootClass;

    public function __construct(string $aggregateRootClass)
    {
        if (!in_array(AggregateInterface::class, (array) class_implements($aggregateRootClass))) {
            $message = sprintf('Class %s must implement %s.', $aggregateRootClass, AggregateInterface::class);
            throw new RuntimeException($message);
        }
        $this->aggregateRootClass = $aggregateRootClass;
    }

    public function getAggregateRootClass(): string
    {
        return $this->aggregateRootClass;
    }

    public function rebuildFromStream(Stream $stream): AggregateInterface
    {
        $class = $this->aggregateRootClass;
        /** @var AggregateInterface $aggregate */
        $aggregate = new $class($stream->getAggregateId());
        $aggregate->replayStream($stream);
        return $aggregate;
    }
}
