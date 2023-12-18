<?php

declare(strict_types=1);

namespace Backslash\Serializer;

use PHPUnit\Framework\TestCase;

class SerializeFunctionTest extends TestCase
{
    /** @test */
    public function it_serializes_and_unserializes_objects(): void
    {
        $serializer = new Serializer(new SerializeFunctionSerializer());

        $object = new TestClass();

        $string = $serializer->serialize($object);
        $newObject = $serializer->deserialize($string);

        $this->assertEquals($object, $newObject);
    }
}
