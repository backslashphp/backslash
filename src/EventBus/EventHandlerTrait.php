<?php

declare(strict_types=1);

namespace Backslash\EventBus;

use Backslash\Event\RecordedEvent;
use RuntimeException;

trait EventHandlerTrait
{
    public function handle(RecordedEvent $recordedEvent): void
    {
        $event = $recordedEvent->getEvent();

        $classParts = explode('\\', $event::class);
        $method = 'handle' . end($classParts);

        if (method_exists($this, $method)) {
            $this->$method($event, $recordedEvent);
        } else {
            throw new RuntimeException(
                sprintf(
                    'Function "%s" must be implemented in class %s',
                    $method,
                    $this::class,
                ),
            );
        }
    }
}
