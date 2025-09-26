<?php

declare(strict_types=1);

namespace Backslash\Event;

/**
 * Represents an event in a domain.
 */
interface EventInterface
{
    public function getIdentifiers(): Identifiers;

    /**
     * Transforms the event payload to an array using only PHP scalar types.
     */
    public function toArray(): array;

    public static function fromArray(array $data): self;
}
