<?php

declare(strict_types=1);

namespace Backslash\CommandDispatcher;

interface DispatcherInterface
{
    public function dispatch(object $command): void;
}
