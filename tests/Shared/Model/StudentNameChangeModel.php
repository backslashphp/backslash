<?php

declare(strict_types=1);

namespace Backslash\Shared\Model;

use Backslash\EventStore\Query\EventClass;
use Backslash\EventStore\Query\Identifier;
use Backslash\EventStore\Query\QueryInterface;
use Backslash\Model\AbstractModel;
use Backslash\Shared\Event\StudentNameChangedEvent;
use Backslash\Shared\Event\StudentRegisteredEvent;

class StudentNameChangeModel extends AbstractModel
{
    private string $studentId;

    private string $name;

    public static function getQuery(string $studentId): QueryInterface
    {
        return EventClass::in(StudentRegisteredEvent::class, StudentNameChangedEvent::class)
            ->and(Identifier::is('studentId', $studentId));
    }

    public function changeName(string $name): void
    {
        if ($name !== $this->name) {
            $this->record(new StudentNameChangedEvent($this->studentId, $this->name, $name));
        }
    }

    protected function applyStudentRegisteredEvent(StudentRegisteredEvent $event): void
    {
        $this->studentId = $event->getStudentId();
        $this->name = $event->getName();
    }

    protected function applyStudentNameChangedEvent(StudentNameChangedEvent $event): void
    {
        $this->name = $event->getNew();
    }
}
