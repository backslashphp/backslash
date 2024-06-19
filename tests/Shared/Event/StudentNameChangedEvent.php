<?php

declare(strict_types=1);

namespace Backslash\Shared\Event;

use Backslash\Domain\EventInterface;
use Backslash\Domain\Identifiers;
use Backslash\Domain\ToArrayTrait;

class StudentNameChangedEvent implements EventInterface
{
    use ToArrayTrait;

    private string $studentId;

    private string $previous;

    private string $new;

    public function __construct(string $studentId, string $previous, string $new)
    {
        $this->studentId = $studentId;
        $this->previous = $previous;
        $this->new = $new;
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

    public function getPrevious(): string
    {
        return $this->previous;
    }

    public function getNew(): string
    {
        return $this->new;
    }
}
