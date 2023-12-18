<?php

declare(strict_types=1);

namespace Backslash\CommandDispatcher;

final class Router implements DispatcherInterface
{
    /** @var HandlerInterface[] */
    private array $map;

    public function dispatch(object $command): void
    {
        $name = $command::class;
        if (isset($this->map[$name])) {
            $this->map[$name]->handle($command);
        }
    }

    public function registerHandler(string $commandClass, HandlerInterface $handler): void
    {
        $this->map[$commandClass] = $handler;
    }
}
