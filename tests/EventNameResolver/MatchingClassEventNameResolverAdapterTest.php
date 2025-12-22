<?php

declare(strict_types=1);

namespace Backslash\EventNameResolver;

use Backslash\Shared\Event\StudentRegisteredEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MatchingClassEventNameResolverAdapterTest extends TestCase
{
    #[Test]
    public function it_resolves_name_from_class(): void
    {
        $resolver = new EventNameResolver(new MatchingClassEventNameResolverAdapter());

        $name = $resolver->resolveName(StudentRegisteredEvent::class);

        $this->assertEquals(StudentRegisteredEvent::class, $name);
    }

    #[Test]
    public function it_resolves_class_from_name(): void
    {
        $resolver = new EventNameResolver(new MatchingClassEventNameResolverAdapter());

        $class = $resolver->resolveClass(StudentRegisteredEvent::class);

        $this->assertEquals(StudentRegisteredEvent::class, $class);
    }
}
