<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Fixtures;

/**
 * Simple user data transfer object
 */
class SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly float $balance,
        public readonly bool $isActive,
        public readonly ?string $email = null,
    ) {
    }
}
