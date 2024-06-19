<?php

declare(strict_types=1);

namespace Backslash\Shared\State;

use Backslash\Domain\AbstractState;
use Backslash\EventStore\Query\EventClass;
use Backslash\EventStore\Query\QueryInterface;
use Backslash\Shared\Event\CourseCreatedEvent;
use Backslash\Shared\Event\StudentRegisteredEvent;
use Backslash\Shared\Event\StudentSubscribedToCourseEvent;
use Backslash\Shared\Event\StudentUnsubscribedFromCourseEvent;
use OutOfBoundsException;
use OverflowException;

class CourseSubscriptionState extends AbstractState
{
    private const MAX_STUDENTS_IN_COURSE = 10;

    private const MAX_COURSE_FOR_STUDENT = 5;

    private array $courses = [];

    private array $students = [];

    public static function getQuery(): QueryInterface
    {
        return EventClass::in(
            CourseCreatedEvent::class,
            StudentRegisteredEvent::class,
            StudentSubscribedToCourseEvent::class,
            StudentUnsubscribedFromCourseEvent::class,
        );
    }

    public function subscribe(string $studentId, string $courseId): void
    {
        $this->assertCourseExists($courseId);
        $this->assertStudentExists($studentId);
        if (isset($this->courses[$courseId][$studentId])) {
            return;
        }
        if (count($this->courses[$courseId]) === self::MAX_STUDENTS_IN_COURSE) {
            throw new OverflowException();
        }
        if (count($this->students[$studentId]) === self::MAX_COURSE_FOR_STUDENT) {
            throw new OverflowException();
        }
        $this->apply(new StudentSubscribedToCourseEvent($studentId, $courseId));
    }

    public function unsubscribe(string $studentId, string $courseId): void
    {
        $this->assertCourseExists($courseId);
        $this->assertStudentExists($studentId);
        if (isset($this->courses[$courseId][$studentId])) {
            $this->apply(new StudentUnsubscribedFromCourseEvent($studentId, $courseId));
        }
    }

    protected function applyCourseCreatedEvent(CourseCreatedEvent $event): void
    {
        $this->courses[$event->getCourseId()] = [];
    }

    protected function applyStudentRegisteredEvent(StudentRegisteredEvent $event): void
    {
        $this->students[$event->getStudentId()] = [];
    }

    protected function applyStudentSubscribedToCourseEvent(StudentSubscribedToCourseEvent $event): void
    {
        $this->courses[$event->getCourseId()][$event->getStudentId()] = $event->getStudentId();
        $this->students[$event->getStudentId()][$event->getCourseId()] = $event->getCourseId();
    }

    protected function applyStudentUnsubscribedFromCourseEvent(StudentUnsubscribedFromCourseEvent $event): void
    {
        unset($this->courses[$event->getCourseId()][$event->getStudentId()]);
        unset($this->students[$event->getStudentId()][$event->getCourseId()]);
    }

    private function assertCourseExists(string $courseId): void
    {
        if (!array_key_exists($courseId, $this->courses)) {
            throw new OutOfBoundsException();
        }
    }

    private function assertStudentExists(string $studentId): void
    {
        if (!array_key_exists($studentId, $this->students)) {
            throw new OutOfBoundsException();
        }
    }
}
