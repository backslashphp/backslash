<?php

declare(strict_types=1);

namespace Backslash\Scenario\Constraint;

use Backslash\Scenario\PublishedEvents;
use PHPUnit\Framework\Constraint\Constraint;
use ReflectionClass;

final class StreamMustContainExactly extends Constraint
{
    private int $count;

    private string $eventFqcn;

    private int $found;

    public function __construct(int $count, string $eventFqcn)
    {
        if ((new ReflectionClass(Constraint::class))->hasMethod('__construct')) {
            parent::__construct();
        }
        $this->count = $count;
        $this->eventFqcn = $eventFqcn;
        $this->found = 0;
    }

    /**
     * @param PublishedEvents $publishedEvents
     */
    public function matches($publishedEvents): bool
    {
        $this->found = 0;
        foreach ($publishedEvents->getAll() as $recordedEvent) {
            if ($recordedEvent->getEvent()::class === $this->eventFqcn) {
                $this->found++;
            }
        }
        return $this->found === $this->count;
    }

    public function toString(): string
    {
        return "contain exactly {$this->count} instance(s) of {$this->eventFqcn}, found {$this->found}";
    }

    protected function failureDescription($other): string
    {
        return 'published events ' . $this->toString();
    }
}
