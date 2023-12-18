<?php

declare(strict_types=1);

namespace Backslash\EventBus;

use Backslash\Aggregate\Stream;

final class EventBus implements EventBusInterface
{
    private Publisher $publisher;

    /** @var MiddlewareInterface[] */
    private array $middlewares;

    private ?EventStreamPublisherInterface $chain = null;

    public function __construct()
    {
        $this->publisher = new Publisher();
        $this->middlewares = [];
        $this->chainMiddlewares();
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

    public function publish(Stream $stream): void
    {
        if (!$this->chain) {
            $this->chainMiddlewares();
        }
        $this->chain->publish($stream);
    }

    public function subscribe(string $eventClass, EventHandlerInterface $subscriber): void
    {
        $this->publisher->subscribe($eventClass, $subscriber);
    }

    private function chainMiddlewares(): void
    {
        $this->chain = array_reduce(
            $this->middlewares,
            fn (
                EventStreamPublisherInterface $carry,
                MiddlewareInterface $item,
            ): EventStreamPublisherInterface => new MiddlewareDelegator($item, $carry),
            $this->publisher,
        );
    }
}
