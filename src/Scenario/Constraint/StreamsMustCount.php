<?php

declare(strict_types=1);

namespace Backslash\Scenario\Constraint;

use Backslash\Scenario\PublishedStreams;
use PHPUnit\Framework\Constraint\Constraint;
use ReflectionClass;

final class StreamsMustCount extends Constraint
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
     * @param PublishedStreams $publishedStreams
     */
    public function matches($publishedStreams): bool
    {
        $count = 0;
        foreach ($publishedStreams->getAll() as $stream) {
            $count += count($stream->getRecordedEvents());
        }
        return $count === $this->count;
    }

    public function toString(): string
    {
        return " have {$this->count} stream(s)";
    }

    protected function failureDescription($other): string
    {
        return 'published streams' . $this->toString();
    }
}
