<?php

declare(strict_types=1);

namespace Backslash\CommandDispatcher;

interface HandlerInterface
{
    public function handle(object $command): void;
}
