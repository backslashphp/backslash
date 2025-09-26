<?php

declare(strict_types=1);

namespace Backslash\StreamEnricher;

use Backslash\Event\RecordedEventStream;

interface StreamEnricherInterface
{
    public function enrich(RecordedEventStream $stream): RecordedEventStream;
}
