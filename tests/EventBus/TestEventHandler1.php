<?php

declare(strict_types=1);

namespace Backslash\EventBus;

use Backslash\Event\EventInterface;
use Backslash\Event\RecordedEvent;
use Backslash\Shared\Event\StudentRegisteredEvent;

class TestEventHandler1 implements EventHandlerInterface
{
    use EventHandlerTrait;

    /** @var EventInterface[] */
    private array $handledEvents = [];

    public static function getSubscribedEventClasses(): array
    {
        return [
            StudentRegisteredEvent::class,
        ];
    }

    /** @return EventInterface[] */
    public function getHandledEvents(): array
    {
        return $this->handledEvents;
    }

    private function handleStudentRegisteredEvent(StudentRegisteredEvent $event, RecordedEvent $recordedEvent): void
    {
        $this->handledEvents[] = $event;
    }
}
