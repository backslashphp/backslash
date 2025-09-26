<?php

declare(strict_types=1);

namespace Backslash\Shared\Event;

use Backslash\Event\EventInterface;
use Backslash\Event\Identifiers;
use Backslash\Event\ToArrayTrait;

class StudentPreferredColorChangedEvent implements EventInterface
{
    use ToArrayTrait;

    private string $studentId;

    private array $colors;

    public function __construct(string $studentId, array $colors)
    {
        $this->studentId = $studentId;
        $this->colors = $colors;
    }

    public function getIdentifiers(): Identifiers
    {
        return new Identifiers([
            'studentId' => $this->studentId,
            'colors' => $this->colors,
        ]);
    }

    public function getStudentId(): string
    {
        return $this->studentId;
    }

    public function getColors(): array
    {
        return $this->colors;
    }
}
