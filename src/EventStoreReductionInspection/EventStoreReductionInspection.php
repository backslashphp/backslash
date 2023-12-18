<?php

declare(strict_types=1);

namespace Backslash\EventStoreReductionInspection;

use Backslash\EventStore\AdapterInterface;

final class EventStoreReductionInspection
{
    private AdapterInterface $adapter;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function inspect(ReductionInspectorInterface $reducer): mixed
    {
        $reducer->reset();
        $this->adapter->inspect($reducer);
        return $reducer->getResult();
    }
}
