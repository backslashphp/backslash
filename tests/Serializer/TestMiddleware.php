<?php

declare(strict_types=1);

namespace Backslash\Serializer;

class TestMiddleware implements MiddlewareInterface
{
    private string $name;

    private Output $output;

    public function __construct(string $name, Output $output)
    {
        $this->name = $name;
        $this->output = $output;
    }

    public function serialize(mixed $value, SerializerInterface $next): string
    {
        $this->output->write('before serialize ' . $this->name);
        $string = $next->serialize($value);
        $this->output->write('after serialize ' . $this->name);
        return $string;
    }

    public function deserialize(string $payload, ?string $type, SerializerInterface $next): mixed
    {
        $this->output->write('before unserialize ' . $this->name);
        $object = $next->deserialize($payload, $type);
        $this->output->write('after unserialize ' . $this->name);
        return $object;
    }
}
