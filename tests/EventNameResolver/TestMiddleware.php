<?php

declare(strict_types=1);

namespace Backslash\EventNameResolver;

use Backslash\Shared\Output;

class TestMiddleware implements MiddlewareInterface
{
    private string $name;

    private Output $output;

    public function __construct(string $name, Output $output)
    {
        $this->name = $name;
        $this->output = $output;
    }

    public function resolveClass(string $eventName, EventNameResolverInterface $next): string
    {
        $this->output->write('before resolveClass ' . $this->name);
        $string = $next->resolveClass($eventName);
        $this->output->write('after resolveClass ' . $this->name);
        return $string;
    }

    public function resolveName(string $eventClass, EventNameResolverInterface $next): string
    {
        $this->output->write('before resolveName ' . $this->name);
        $object = $next->resolveName($eventClass);
        $this->output->write('after resolveName ' . $this->name);
        return $object;
    }
}
