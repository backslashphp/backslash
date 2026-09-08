<?php

declare(strict_types=1);

namespace Backslash\EventStore\Query;

final class EventClass
{
    private array $values;

    private function __construct(string ...$values)
    {
        $this->values = $values;
    }

    public static function in(string ...$eventClasses): self
    {
        return new self(...$eventClasses);
    }

    public function getValues(): array
    {
        return $this->values;
    }
}
