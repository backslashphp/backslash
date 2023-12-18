<?php

declare(strict_types=1);

namespace Backslash\ProjectionStore;

use Backslash\Projection\ProjectionInterface;

final class ProjectionDescriptor
{
    private string $id;

    private string $class;

    public function __construct(string $id, string $class)
    {
        $this->id = $id;
        $this->class = $class;
    }

    public static function fromProjection(ProjectionInterface $projection): self
    {
        return new self($projection->getId(), $projection::class);
    }

    public function getKey(): string
    {
        return $this->id . '-' . $this->class;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getClass(): string
    {
        return $this->class;
    }
}
