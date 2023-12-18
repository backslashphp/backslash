<?php

declare(strict_types=1);

namespace Backslash\CommandDispatcher;

use RuntimeException;

trait HandleCommandTrait
{
    public function handle(object $command): void
    {
        $classParts = explode('\\', $command::class);
        $method = 'handle' . end($classParts);

        if (!method_exists($this, $method)) {
            $message = sprintf('Function "%s" must be implemented in class %s', $method, $this::class);
            throw new RuntimeException($message);
        }
        $this->$method($command);
    }
}
