<?php

declare(strict_types=1);

namespace Backslash\CommandDispatcher;

interface MiddlewareInterface
{
    public function dispatch(object $command, DispatcherInterface $next): void;
}
