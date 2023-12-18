<?php

declare(strict_types=1);

namespace Backslash\ProjectionStore;

use Exception;

final class ProjectionNotFoundException extends Exception
{
    public static function forProjection(string $id, string $class): self
    {
        $message = sprintf('Projection with ID %s and class %s not found.', $id, $class);
        return new self($message);
    }
}
