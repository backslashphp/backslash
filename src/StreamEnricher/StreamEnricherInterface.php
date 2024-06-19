<?php

declare(strict_types=1);

namespace Backslash\StreamEnricher;

use Backslash\Domain\RecordedEventStream;

interface StreamEnricherInterface
{
    public function enrich(RecordedEventStream $stream): RecordedEventStream;
}
