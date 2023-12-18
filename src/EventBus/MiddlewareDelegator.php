<?php

declare(strict_types=1);

namespace Backslash\EventBus;

use Backslash\Aggregate\Stream;

final class MiddlewareDelegator implements EventStreamPublisherInterface
{
    private MiddlewareInterface $middleware;

    private ?EventStreamPublisherInterface $next;

    public function __construct(MiddlewareInterface $middleware, ?EventStreamPublisherInterface $next = null)
    {
        $this->middleware = $middleware;
        $this->next = $next;
    }

    public function publish(Stream $stream): void
    {
        $this->middleware->publish($stream, $this->next);
    }
}
