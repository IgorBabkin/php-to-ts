<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Fixtures;

/**
 * User role enumeration
 */
enum RoleEnum: string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case GUEST = 'guest';
}
