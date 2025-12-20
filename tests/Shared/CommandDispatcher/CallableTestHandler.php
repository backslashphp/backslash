<?php

declare(strict_types=1);

namespace Backslash\Shared\CommandDispatcher;

use Backslash\CommandDispatcher\HandlerInterface;

class CallableTestHandler implements HandlerInterface
{
    public function __construct(
        private ?\Closure $callback = null,
    ) {
    }

    public function handle(object $command): void
    {
        if ($this->callback) {
            ($this->callback)($command);
        }
    }
}
