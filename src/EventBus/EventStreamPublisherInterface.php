<?php

declare(strict_types=1);

namespace Backslash\EventBus;

use Backslash\Aggregate\Stream;

interface EventStreamPublisherInterface
{
    public function publish(Stream $stream): void;
}
