<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Fixtures;

/**
 * City data transfer object
 */
class CityDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $countryCode,
        public readonly int $population,
    ) {
    }
}
