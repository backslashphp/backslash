<?php

declare(strict_types=1);

namespace Backslash\EventStoreReductionInspection;

use Backslash\EventStore\InspectorInterface;

interface ReductionInspectorInterface extends InspectorInterface
{
    public function getResult(): mixed;

    public function reset(): void;
}
