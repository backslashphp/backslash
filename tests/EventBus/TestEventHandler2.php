<?php

declare(strict_types=1);

namespace Backslash\EventBus;

use Backslash\Domain\EventInterface;
use Backslash\Domain\RecordedEvent;
use Backslash\Shared\Event\StudentNameChangedEvent;

class TestEventHandler2 implements EventHandlerInterface
{
    use EventHandlerTrait;

    /** @var EventInterface[] */
    private array $handledEvents = [];

    public static function getSubscribedEventClasses(): array
    {
        return [
            StudentNameChangedEvent::class,
        ];
    }

    /** @return EventInterface[] */
    public function getHandledEvents(): array
    {
        return $this->handledEvents;
    }

    private function handleStudentNameChangedEvent(StudentNameChangedEvent $event, RecordedEvent $recordedEvent): void
    {
        $this->handledEvents[] = $event;
    }
}
