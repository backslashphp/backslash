<?php

declare(strict_types=1);

namespace Backslash\Scenario;

use Backslash\Shared\Projection\TestBarProjection;
use Backslash\Shared\Projection\TestFooProjection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UpdatedProjectionsTest extends TestCase
{
    #[Test]
    public function it_counts_projections(): void
    {
        $updatedProjections = new UpdatedProjections($this->getProjections());

        $this->assertCount(2, $updatedProjections);
    }

    #[Test]
    public function it_returns_projections_of_type(): void
    {
        $updatedProjections = new UpdatedProjections($this->getProjections());

        $this->assertCount(1, $updatedProjections->getAllOf(TestFooProjection::class));
    }

    #[Test]
    public function it_returns_all_projections(): void
    {
        $updatedProjections = new UpdatedProjections($this->getProjections());

        $projections = $updatedProjections->getAll();

        $this->assertEquals(TestFooProjection::class, get_class($projections[0]));
        $this->assertEquals(TestBarProjection::class, get_class($projections[1]));
    }

    private function getProjections(): array
    {
        return [
            new TestFooProjection('1'),
            new TestBarProjection('2'),
        ];
    }
}
