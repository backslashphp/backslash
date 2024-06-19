<?php

declare(strict_types=1);

namespace Backslash\Scenario\Constraint;

use Backslash\Scenario\PublishedEvents;
use PHPUnit\Framework\Constraint\Constraint;
use ReflectionClass;

final class StreamMustCount extends Constraint
{
    private int $count;

    public function __construct(int $count)
    {
        if ((new ReflectionClass(Constraint::class))->hasMethod('__construct')) {
            parent::__construct();
        }
        $this->count = $count;
    }

    /**
     * @param PublishedEvents $publishedEvents
     */
    public function matches($publishedEvents): bool
    {
        return count($publishedEvents) === $this->count;
    }

    public function toString(): string
    {
        return "must count {$this->count} event(s)";
    }

    protected function failureDescription($other): string
    {
        return 'published events ' . $this->toString();
    }
}
