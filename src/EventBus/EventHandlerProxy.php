<?php

declare(strict_types=1);

namespace Backslash\EventBus;

use Backslash\Aggregate\RecordedEvent;

final class EventHandlerProxy implements EventHandlerInterface
{
    /** @var callable */
    private $resolver;

    private ?EventHandlerInterface $handler = null;

    public function __construct(callable $resolver)
    {
        $this->resolver = $resolver;
    }

    public function handle(string $aggregateId, RecordedEvent $recordedEvent): void
    {
        if (!$this->handler) {
            $resolver = $this->resolver;
            $this->handler = $resolver();
        }
        $this->handler->handle($aggregateId, $recordedEvent);
    }
}
