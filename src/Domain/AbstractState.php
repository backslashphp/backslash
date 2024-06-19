<?php

declare(strict_types=1);

namespace Backslash\Domain;

abstract class AbstractState implements StateInterface
{
    use StateTrait;
}
