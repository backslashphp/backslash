<?php

declare(strict_types=1);

namespace Backslash\Event;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ToArrayTraitTest extends TestCase
{
    #[Test]
    public function fromArray_with_variadic_parameter(): void
    {
        $event = VariadicEvent::fromArray([
            'teamId' => 'team-1',
            'userIds' => ['user-1', 'user-2', 'user-3'],
        ]);

        $this->assertInstanceOf(VariadicEvent::class, $event);
        $this->assertSame('team-1', $event->teamId);
        $this->assertSame(['user-1', 'user-2', 'user-3'], $event->userIds);
    }

    #[Test]
    public function fromArray_with_empty_variadic_parameter(): void
    {
        $event = VariadicEvent::fromArray([
            'teamId' => 'team-1',
            'userIds' => [],
        ]);

        $this->assertSame('team-1', $event->teamId);
        $this->assertSame([], $event->userIds);
    }

    #[Test]
    public function toArray_and_fromArray_roundtrip_with_variadic(): void
    {
        $original = new VariadicEvent('team-1', 'user-1', 'user-2');
        $data = $original->toArray();
        $restored = VariadicEvent::fromArray($data);

        $this->assertSame($original->teamId, $restored->teamId);
        $this->assertSame($original->userIds, $restored->userIds);
    }

    #[Test]
    public function fromArray_ignores_extra_keys(): void
    {
        $event = VariadicEvent::fromArray([
            'teamId' => 'team-1',
            'userIds' => ['user-1'],
            'removedAttribute' => 'should be ignored',
        ]);

        $this->assertSame('team-1', $event->teamId);
        $this->assertSame(['user-1'], $event->userIds);
    }

    #[Test]
    public function fromArray_throws_when_required_parameter_is_missing(): void
    {
        $this->expectException(RuntimeException::class);

        VariadicEvent::fromArray([
            'userIds' => ['user-1'],
        ]);
    }
}

class VariadicEvent implements EventInterface
{
    use ToArrayTrait;

    public readonly array $userIds;

    public function __construct(
        public readonly string $teamId,
        string ...$userIds,
    ) {
        $this->userIds = $userIds;
    }

    public function getIdentifiers(): Identifiers
    {
        return new Identifiers(['teamId' => $this->teamId]);
    }
}
