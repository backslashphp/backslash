<?php

declare(strict_types=1);

namespace Backslash\EventBus;

use Backslash\Domain\RecordedEvent;

final class EventHandlerProxy implements EventHandlerInterface
{
    /** @var callable */
    private $resolver;

    private ?EventHandlerInterface $handler = null;

    public function __construct(callable $resolver)
    {
        $this->resolver = $resolver;
    }

    public function handle(RecordedEvent $recordedEvent): void
    {
        if (!$this->handler) {
            $resolver = $this->resolver;
            $this->handler = $resolver();
        }
        $this->handler->handle($recordedEvent);
    }
}
