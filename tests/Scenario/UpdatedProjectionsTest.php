<?php

declare(strict_types=1);

namespace Backslash\Scenario;

use PHPUnit\Framework\TestCase;

class UpdatedProjectionsTest extends TestCase
{
    /** @test */
    public function it_counts_projections(): void
    {
        $updatedProjections = new UpdatedProjections($this->getProjections());

        $this->assertCount(2, $updatedProjections);
    }

    /** @test */
    public function it_returns_projections_of_type(): void
    {
        $updatedProjections = new UpdatedProjections($this->getProjections());

        $this->assertCount(1, $updatedProjections->getAllOf(TestProjection1::class));
    }

    /** @test */
    public function it_returns_all_projections(): void
    {
        $updatedProjections = new UpdatedProjections($this->getProjections());

        $projections = $updatedProjections->getAll();

        $this->assertEquals(TestProjection1::class, get_class($projections[0]));
        $this->assertEquals(TestProjection2::class, get_class($projections[1]));
    }

    private function getProjections(): array
    {
        return [
            new TestProjection1(),
            new TestProjection2(),
        ];
    }
}
