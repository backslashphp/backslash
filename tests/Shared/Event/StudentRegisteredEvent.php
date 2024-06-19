<?php

declare(strict_types=1);

namespace Backslash\Shared\Event;

use Backslash\Domain\EventInterface;
use Backslash\Domain\Identifiers;
use Backslash\Domain\ToArrayTrait;

class StudentRegisteredEvent implements EventInterface
{
    use ToArrayTrait;

    private string $studentId;

    private string $name;

    public function __construct(string $studentId, string $name)
    {
        $this->studentId = $studentId;
        $this->name = $name;
    }

    public function getIdentifiers(): Identifiers
    {
        return new Identifiers([
            'studentId' => $this->studentId,
        ]);
    }

    public function getStudentId(): string
    {
        return $this->studentId;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
