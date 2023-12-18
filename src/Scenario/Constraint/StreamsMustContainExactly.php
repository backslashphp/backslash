<?php

declare(strict_types=1);

namespace Backslash\Scenario\Constraint;

use Backslash\Scenario\PublishedStreams;
use PHPUnit\Framework\Constraint\Constraint;
use ReflectionClass;

final class StreamsMustContainExactly extends Constraint
{
    private int $count;

    private string $eventClass;

    private int $found;

    public function __construct(int $count, string $eventClass)
    {
        if ((new ReflectionClass(Constraint::class))->hasMethod('__construct')) {
            parent::__construct();
        }
        $this->count = $count;
        $this->eventClass = $eventClass;
        $this->found = 0;
    }

    /**
     * @param PublishedStreams $publishedStreams
     */
    public function matches($publishedStreams): bool
    {
        $this->found = 0;
        foreach ($publishedStreams->getAll() as $stream) {
            foreach ($stream->getRecordedEvents() as $recordedEvent) {
                if ($recordedEvent->getEvent()::class === $this->eventClass) {
                    $this->found++;
                }
            }
        }
        return $this->found === $this->count;
    }

    public function toString(): string
    {
        return " contain exactly {$this->count} instance(s) of {$this->eventClass}, found {$this->found}";
    }

    protected function failureDescription($other): string
    {
        return 'published streams' . $this->toString();
    }
}
