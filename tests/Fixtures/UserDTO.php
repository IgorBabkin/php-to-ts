<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Fixtures;

/**
 * User with nested address
 */
class UserDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $username,
        public readonly AddressDTO $address,
        public readonly ?AddressDTO $billingAddress = null,
    ) {
    }
}
