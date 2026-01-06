<?php

declare(strict_types=1);

namespace Backslash\Scenario;

enum EventPublishingMode
{
    case DETECT;
    case ALWAYS;
    case NEVER;
}
