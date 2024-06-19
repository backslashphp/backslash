<?php

declare(strict_types=1);

namespace Backslash\EventStore\Query;

enum LogicOperator: string
{
    case AND = 'AND';
    case OR = 'OR';
}
