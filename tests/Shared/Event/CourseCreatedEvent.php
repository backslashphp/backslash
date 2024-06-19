<?php

declare(strict_types=1);

namespace Backslash\Shared\Event;

use Backslash\Domain\EventInterface;
use Backslash\Domain\Identifiers;
use Backslash\Domain\ToArrayTrait;

class CourseCreatedEvent implements EventInterface
{
    use ToArrayTrait;

    private string $courseId;

    private string $code;

    private string $name;

    public function __construct(string $courseId, string $code, string $name)
    {
        $this->courseId = $courseId;
        $this->code = $code;
        $this->name = $name;
    }

    public function getIdentifiers(): Identifiers
    {
        return new Identifiers([
            'courseId' => $this->courseId,
        ]);
    }

    public function getCourseId(): string
    {
        return $this->courseId;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
