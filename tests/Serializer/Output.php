<?php

declare(strict_types=1);

namespace Backslash\Serializer;

class Output
{
    /** @var string[] */
    private array $messages = [];

    public function write(string $message): void
    {
        $this->messages[] = $message;
    }

    public function read(): array
    {
        return $this->messages;
    }

    public function clear(): void
    {
        $this->messages = [];
    }
}
