<?php

declare(strict_types=1);

namespace Backslash\CommandDispatcher;

final class HandlerProxy implements HandlerInterface
{
    /** @var callable */
    private $resolver;

    private ?HandlerInterface $handler = null;

    public function __construct(callable $resolver)
    {
        $this->resolver = $resolver;
    }

    public function handle(object $command): void
    {
        if (!$this->handler) {
            $resolver = $this->resolver;
            $this->handler = $resolver();
        }
        $this->handler->handle($command);
    }
}
