<?php

declare(strict_types=1);

namespace Backslash\StreamEnricher;

use Backslash\Aggregate\Stream;

interface StreamEnricherInterface
{
    public function enrich(Stream $stream): Stream;
}
