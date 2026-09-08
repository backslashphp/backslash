<?php

declare(strict_types=1);

namespace Backslash\Shared\Model;

use Backslash\EventStore\Query\EventClass;
use Backslash\EventStore\Query\Identifier;
use Backslash\EventStore\Query\Query;
use Backslash\Model\AbstractModel;
use Backslash\Shared\Event\StudentRegisteredEvent;

class StudentRegistrationModel extends AbstractModel
{
    public static function getQuery(string $studentId): Query
    {
        return (new Query())->withItem(EventClass::in(StudentRegisteredEvent::class), Identifier::is('studentId', $studentId));
    }

    public function register(string $studentId, string $name): void
    {
        $this->record(new StudentRegisteredEvent($studentId, $name));
    }
}
