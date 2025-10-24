<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Fixtures;

/**
 * DTO with arrays and collections
 */
class CollectionDTO
{
    public function __construct(
        /** @var string[] */
        public readonly array $tags,
        /** @var AddressDTO[] */
        public readonly array $addresses,
        public readonly array $metadata,
        /** @var int[] */
        public readonly ?array $scores = null,
    ) {
    }
}
