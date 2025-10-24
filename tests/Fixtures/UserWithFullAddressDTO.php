<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Fixtures;

/**
 * User with deeply nested address
 */
class UserWithFullAddressDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly AddressWithCityDTO $address,
    ) {
    }
}
