<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Fixtures;

/**
 * Priority levels with integer backing
 */
enum PriorityEnum: int
{
    case LOW = 1;
    case MEDIUM = 2;
    case HIGH = 3;
    case CRITICAL = 4;
}
