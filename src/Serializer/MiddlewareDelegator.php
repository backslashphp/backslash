<?php

declare(strict_types=1);

namespace Backslash\Serializer;

final class MiddlewareDelegator implements SerializerInterface
{
    private MiddlewareInterface $middleware;

    private ?SerializerInterface $next = null;

    public function __construct(MiddlewareInterface $middleware, ?SerializerInterface $next)
    {
        $this->middleware = $middleware;
        $this->next = $next;
    }

    public function serialize(mixed $value): string
    {
        return $this->middleware->serialize($value, $this->next);
    }

    public function deserialize(string $payload, ?string $type = null): mixed
    {
        return $this->middleware->deserialize($payload, $type, $this->next);
    }
}
