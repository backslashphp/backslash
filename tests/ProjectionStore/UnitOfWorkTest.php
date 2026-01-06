<?php

declare(strict_types=1);

namespace Backslash\ProjectionStore;

use Backslash\Shared\Projection\TestFooProjection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UnitOfWorkTest extends TestCase
{
    #[Test]
    public function it_tracks_stored_and_removed_projection(): void
    {
        $unit = new UnitOfWork();
        $this->assertEmpty($unit->getStored());
        $this->assertEmpty($unit->getRemoved());

        $unit->store(new TestFooProjection('123'));
        $this->assertCount(1, $unit->getStored());
        $this->assertNotNull($unit->getOneStored('123', TestFooProjection::class));
        $this->assertEmpty($unit->getRemoved());

        $unit->remove('123', TestFooProjection::class);
        $this->assertEmpty($unit->getStored());
        $this->assertNull($unit->getOneStored('123', TestFooProjection::class));
        $this->assertCount(1, $unit->getRemoved());

        $unit->remove('123', TestFooProjection::class);
        $this->assertEmpty($unit->getStored());
        $this->assertCount(1, $unit->getRemoved());

        $unit->store(new TestFooProjection('123'));
        $this->assertCount(1, $unit->getStored());
        $this->assertEmpty($unit->getRemoved());
    }
}
