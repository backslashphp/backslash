<?php

declare(strict_types=1);

namespace Backslash\Scenario\Constraint;

use Backslash\Scenario\PublishedStreams;
use PHPUnit\Framework\Constraint\Constraint;
use ReflectionClass;

final class StreamsMustContain extends Constraint
{
    private string $eventClass;

    public function __construct(string $eventClass)
    {
        if ((new ReflectionClass(Constraint::class))->hasMethod('__construct')) {
            parent::__construct();
        }
        $this->eventClass = $eventClass;
    }

    /**
     * @param PublishedStreams $publishedStreams
     */
    public function matches($publishedStreams): bool
    {
        foreach ($publishedStreams->getAll() as $stream) {
            foreach ($stream->getRecordedEvents() as $recordedEvent) {
                if ($recordedEvent->getEvent()::class === $this->eventClass) {
                    return true;
                }
            }
        }
        return false;
    }

    public function toString(): string
    {
        return " contain instance(s) of {$this->eventClass}";
    }

    protected function failureDescription($other): string
    {
        return 'published streams' . $this->toString();
    }
}
