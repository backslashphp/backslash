<?php

declare(strict_types=1);

namespace Backslash\Serializer;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

class MiddlewareTest extends TestCase
{
    #[Test]
    public function it_executes_middlewares_in_lifo_order(): void
    {
        $output = new Output();
        $mw1 = new TestMiddleware('mw1', $output);
        $mw2 = new TestMiddleware('mw2', $output);
        $mw3 = new TestMiddleware('mw3', $output);

        $serializer = new Serializer(new SerializeFunctionSerializer());
        $serializer->addMiddleware($mw1);
        $serializer->addMiddleware($mw2);
        $serializer->addMiddleware($mw3);

        $serializer->deserialize($serializer->serialize(new stdClass()));
        $this->assertEquals(
            $output->read(),
            [
                'before serialize mw3',
                'before serialize mw2',
                'before serialize mw1',
                'after serialize mw1',
                'after serialize mw2',
                'after serialize mw3',
                'before unserialize mw3',
                'before unserialize mw2',
                'before unserialize mw1',
                'after unserialize mw1',
                'after unserialize mw2',
                'after unserialize mw3',
            ],
        );
    }

    #[Test]
    public function it_adds_inner_middleware_closest_to_core(): void
    {
        $output = new Output();

        $serializer = new Serializer(new SerializeFunctionSerializer());
        $serializer->addMiddleware(new TestMiddleware('outer', $output));
        $serializer->addInnerMiddleware(new TestMiddleware('inner', $output));

        $serializer->serialize(new stdClass());
        $this->assertEquals(
            [
                'before serialize outer',
                'before serialize inner',
                'after serialize inner',
                'after serialize outer',
            ],
            $output->read(),
        );
    }
}
