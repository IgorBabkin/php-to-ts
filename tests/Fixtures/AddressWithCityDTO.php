<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Fixtures;

/**
 * Address with city details
 */
class AddressWithCityDTO
{
    public function __construct(
        public readonly string $street,
        public readonly string $zipCode,
        public readonly CityDTO $city,
    ) {
    }
}
