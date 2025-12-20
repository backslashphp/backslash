<?php

declare(strict_types=1);

namespace Backslash\Shared\CommandDispatcher;

readonly class ConfigurableTestCommand
{
    public function __construct(
        public string $id = 'test',
    ) {
    }
}
