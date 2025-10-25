<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Fixtures;

/**
 * DTO with array types in constructor @param tags
 */
class ConstructorParamArrayDTO
{
    /**
     * @param array<AddressDTO> $addresses
     * @param array<string, int> $scores
     * @param array<string>|null $tags
     */
    public function __construct(
        public readonly array $addresses,
        public readonly array $scores,
        public readonly ?array $tags,
    ) {
    }
}
