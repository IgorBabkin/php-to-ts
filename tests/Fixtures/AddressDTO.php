<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Fixtures;

/**
 * Address data transfer object
 */
class AddressDTO
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $zipCode,
        public readonly ?string $country = null,
    ) {
    }
}
