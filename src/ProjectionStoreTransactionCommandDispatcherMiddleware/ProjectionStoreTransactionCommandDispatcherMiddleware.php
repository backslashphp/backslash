<?php

declare(strict_types=1);

namespace Backslash\ProjectionStoreTransactionCommandDispatcherMiddleware;

use Backslash\CommandDispatcher\DispatcherInterface;
use Backslash\CommandDispatcher\MiddlewareInterface;
use Backslash\ProjectionStore\ProjectionStoreInterface;
use Throwable;

final class ProjectionStoreTransactionCommandDispatcherMiddleware implements MiddlewareInterface
{
    private ProjectionStoreInterface $projections;

    private int $nestedLevels;

    public function __construct(ProjectionStoreInterface $projections)
    {
        $this->projections = $projections;
        $this->nestedLevels = 0;
    }

    public function dispatch(object $command, DispatcherInterface $next): void
    {
        try {
            $this->nestedLevels++;
            $next->dispatch($command);
            $this->nestedLevels--;
            if ($this->nestedLevels === 0) {
                $this->projections->commit();
            }
        } catch (Throwable $t) {
            $this->projections->rollback();
            $this->nestedLevels = 0;
            throw $t;
        }
    }
}
