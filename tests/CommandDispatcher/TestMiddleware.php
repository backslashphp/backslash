<?php

declare(strict_types=1);

namespace Backslash\CommandDispatcher;

class TestMiddleware implements MiddlewareInterface
{
    private string $name;

    private array $output;

    public function __construct(string $name, array &$output)
    {
        $this->name = $name;
        $this->output = &$output;
    }

    public function dispatch(object $command, DispatcherInterface $next): void
    {
        $this->output[] = 'before ' . $this->name;
        $next->dispatch($command);
        $this->output[] = 'after ' . $this->name;
    }
}
