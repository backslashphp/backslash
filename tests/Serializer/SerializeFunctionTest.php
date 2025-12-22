<?php

declare(strict_types=1);

namespace Backslash\Serializer;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SerializeFunctionTest extends TestCase
{
    #[Test]
    public function it_serializes_and_unserializes_objects(): void
    {
        $serializer = new Serializer(new SerializeFunctionSerializer());

        $object = new TestClass();

        $string = $serializer->serialize($object);
        $newObject = $serializer->deserialize($string);

        $this->assertEquals($object, $newObject);
    }
}
