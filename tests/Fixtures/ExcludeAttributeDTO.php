<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Fixtures;

use PhpToTs\Attribute\Exclude;

/**
 * DTO with excluded properties
 */
class ExcludeAttributeDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,

        /**
         * This should be excluded from TypeScript
         */
        #[Exclude]
        public readonly string $internalSecret,

        public readonly string $email,

        #[Exclude]
        public readonly ?string $passwordHash = null,
    ) {}
}
