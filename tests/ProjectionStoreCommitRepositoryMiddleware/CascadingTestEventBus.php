<?php

declare(strict_types=1);

namespace Backslash\ProjectionStoreCommitRepositoryMiddleware;

use Backslash\Event\RecordedEventStream;
use Backslash\EventBus\EventBusInterface;
use Backslash\EventBus\EventHandlerInterface;
use Closure;

class CascadingTestEventBus implements EventBusInterface
{
    public ?Closure $onPublish = null;

    public function publish(RecordedEventStream $stream): void
    {
        if ($this->onPublish !== null) {
            ($this->onPublish)($stream);
        }
    }

    public function subscribe(string $eventClass, EventHandlerInterface $subscriber): void
    {
    }

    public function subscribeAll(EventHandlerInterface $subscriber): void
    {
    }
}
