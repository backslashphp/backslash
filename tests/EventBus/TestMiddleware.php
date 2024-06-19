<?php

declare(strict_types=1);

namespace Backslash\EventBus;

use Backslash\Domain\RecordedEventStream;

class TestMiddleware implements MiddlewareInterface
{
    private string $name;

    private array $output;

    public function __construct(string $name, array &$output)
    {
        $this->name = $name;
        $this->output = &$output;
    }

    public function publish(RecordedEventStream $stream, EventStreamPublisherInterface $next): void
    {
        $this->output[] = 'before ' . $this->name;
        $next->publish($stream);
        $this->output[] = 'after ' . $this->name;
    }
}
