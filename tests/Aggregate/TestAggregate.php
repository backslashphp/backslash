<?php

declare(strict_types=1);

namespace Backslash\Aggregate;

class TestAggregate implements AggregateInterface
{
    use AggregateRootTrait;

    private ?string $name = null;

    public static function getType(): string
    {
        return 'test-aggregate';
    }

    public static function create(string $id, string $name): self
    {
        $me = new self($id);
        $me->apply(new TestEvent($name, 1, []));
        return $me;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        if ($this->name !== $name) {
            $this->apply(new TestEvent($name, 1, []));
        }
    }

    private function applyTestEvent(TestEvent $event): void
    {
        $this->name = $event->getString();
    }
}
