<?php

declare(strict_types=1);

namespace Backslash\EventBus;

interface EventBusInterface extends EventStreamPublisherInterface
{
    public function subscribe(string $eventClass, EventHandlerInterface $subscriber): void;
}
