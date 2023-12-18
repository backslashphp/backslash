<?php

declare(strict_types=1);

namespace Backslash\Serializer;

final class Serializer implements SerializerInterface
{
    private AdapterInterface $adapter;

    /** @var MiddlewareInterface[] */
    private array $middlewares = [];

    private ?SerializerInterface $chain = null;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $this->middlewares = [];
        $this->chainMiddlewares();
    }

    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
        $this->chainMiddlewares();
    }

    /** @return MiddlewareInterface[] */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function serialize(mixed $value): string
    {
        return $this->chain->serialize($value);
    }

    public function deserialize(string $payload, ?string $type = null): mixed
    {
        return $this->chain->deserialize($payload, $type);
    }

    private function chainMiddlewares(): void
    {
        $this->chain = array_reduce(
            $this->middlewares,
            fn (SerializerInterface $carry, MiddlewareInterface $item): SerializerInterface => new MiddlewareDelegator(
                $item,
                $carry,
            ),
            $this->adapter,
        );
    }
}
