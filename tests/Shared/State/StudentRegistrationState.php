<?php

declare(strict_types=1);

namespace Backslash\Shared\State;

use Backslash\Domain\AbstractState;
use Backslash\EventStore\Query\EventClass;
use Backslash\EventStore\Query\Identifier;
use Backslash\EventStore\Query\QueryInterface;
use Backslash\Shared\Event\StudentRegisteredEvent;

class StudentRegistrationState extends AbstractState
{
    public static function getQuery(string $studentId): QueryInterface
    {
        return EventClass::is(StudentRegisteredEvent::class)
            ->and(Identifier::is('studentId', $studentId));
    }

    public function subscribe(string $studentId, string $name): void
    {
        $this->apply(new StudentRegisteredEvent($studentId, $name));
    }
}
