<?php

declare(strict_types=1);

namespace Backslash\Shared\Event;

use Backslash\Event\EventInterface;
use Backslash\Event\Identifiers;
use Backslash\Event\ToArrayTrait;

class StudentSubscribedToCourseEvent implements EventInterface
{
    use ToArrayTrait;

    private string $studentId;

    private string $courseId;

    public function __construct(string $studentId, string $courseId)
    {
        $this->studentId = $studentId;
        $this->courseId = $courseId;
    }

    public function getIdentifiers(): Identifiers
    {
        return new Identifiers([
            'studentId' => $this->studentId,
            'courseId' => $this->courseId,
        ]);
    }

    public function getStudentId(): string
    {
        return $this->studentId;
    }

    public function getCourseId(): string
    {
        return $this->courseId;
    }
}
