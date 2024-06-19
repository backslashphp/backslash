<?php

declare(strict_types=1);

namespace Backslash\Domain;

/**
 * Represents an event in a domain model.
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
