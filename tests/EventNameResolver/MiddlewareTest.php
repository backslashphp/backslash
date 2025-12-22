<?php

declare(strict_types=1);

namespace Backslash\EventNameResolver;

use Backslash\Shared\Event\StudentRegisteredEvent;
use Backslash\Shared\Output;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MiddlewareTest extends TestCase
{
    #[Test]
    public function it_executes_middlewares_in_lifo_order(): void
    {
        $output = new Output();
        $mw1 = new TestMiddleware('mw1', $output);
        $mw2 = new TestMiddleware('mw2', $output);
        $mw3 = new TestMiddleware('mw3', $output);

        $resolver = new EventNameResolver(new MatchingClassEventNameResolverAdapter());
        $resolver->addMiddleware($mw1);
        $resolver->addMiddleware($mw2);
        $resolver->addMiddleware($mw3);

        $resolver->resolveClass($resolver->resolveName(StudentRegisteredEvent::class));
        $this->assertEquals(
            $output->read(),
            [
                'before resolveName mw3',
                'before resolveName mw2',
                'before resolveName mw1',
                'after resolveName mw1',
                'after resolveName mw2',
                'after resolveName mw3',
                'before resolveClass mw3',
                'before resolveClass mw2',
                'before resolveClass mw1',
                'after resolveClass mw1',
                'after resolveClass mw2',
                'after resolveClass mw3',
            ],
        );
    }
}
