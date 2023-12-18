<?php

declare(strict_types=1);

namespace Backslash\EventBus;

use Backslash\Aggregate\Stream;

interface MiddlewareInterface
{
    public function publish(Stream $stream, EventStreamPublisherInterface $next): void;
}
