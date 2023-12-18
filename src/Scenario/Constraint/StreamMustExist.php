<?php

declare(strict_types=1);

namespace Backslash\Scenario\Constraint;

use Backslash\Scenario\PublishedStreams;
use PHPUnit\Framework\Constraint\Constraint;
use ReflectionClass;

final class StreamMustExist extends Constraint
{
    private string $aggregateId;

    public function __construct(string $aggregateId)
    {
        if ((new ReflectionClass(Constraint::class))->hasMethod('__construct')) {
            parent::__construct();
        }
        $this->aggregateId = $aggregateId;
    }

    /**
     * @param PublishedStreams $publishedStreams
     */
    public function matches($publishedStreams): bool
    {
        foreach ($publishedStreams->getAll() as $stream) {
            if ($stream->getAggregateId() === $this->aggregateId) {
                return true;
            }
        }
        return false;
    }

    public function toString(): string
    {
        return " contain a stream with ID {$this->aggregateId}";
    }

    protected function failureDescription($other): string
    {
        return 'published streams' . $this->toString();
    }
}
