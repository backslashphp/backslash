<?php

declare(strict_types=1);

namespace Backslash\Scenario\Constraint;

use Backslash\Scenario\PublishedEvents;
use PHPUnit\Framework\Constraint\Constraint;
use ReflectionClass;

final class PublishedEventsMustContainOnly extends Constraint
{
    private string $eventFqcn;

    public function __construct(string $eventFqcn)
    {
        if ((new ReflectionClass(Constraint::class))->hasMethod('__construct')) {
            parent::__construct();
        }
        $this->eventFqcn = $eventFqcn;
    }

    /**
     * @param PublishedEvents $publishedEvents
     */
    public function matches($publishedEvents): bool
    {
        $result = true;
        foreach ($publishedEvents->getAll() as $recordedEvent) {
            $result = $result && ($recordedEvent->getEvent()::class === $this->eventFqcn);
        }
        return $result;
    }

    public function toString(): string
    {
        return "must contain only instances of {$this->eventFqcn}";
    }

    protected function failureDescription($other): string
    {
        return 'published events ' . $this->toString();
    }
}
