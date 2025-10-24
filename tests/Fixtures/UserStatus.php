<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Fixtures;

/**
 * User status enum
 */
enum UserStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
    case DELETED = 'deleted';
}
