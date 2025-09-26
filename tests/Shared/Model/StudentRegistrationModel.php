<?php

declare(strict_types=1);

namespace Backslash\Shared\Model;

use Backslash\EventStore\Query\EventClass;
use Backslash\EventStore\Query\Identifier;
use Backslash\EventStore\Query\QueryInterface;
use Backslash\Model\AbstractModel;
use Backslash\Shared\Event\StudentRegisteredEvent;

class StudentRegistrationModel extends AbstractModel
{
    public static function getQuery(string $studentId): QueryInterface
    {
        return EventClass::is(StudentRegisteredEvent::class)
            ->and(Identifier::is('studentId', $studentId));
    }

    public function register(string $studentId, string $name): void
    {
        $this->record(new StudentRegisteredEvent($studentId, $name));
    }
}
