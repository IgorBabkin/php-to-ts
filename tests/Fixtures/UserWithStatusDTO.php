<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Fixtures;

use DateTimeImmutable;

/**
 * User with status and date
 */
class UserWithStatusDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly UserStatus $status,
        public readonly DateTimeImmutable $createdAt,
        public readonly ?DateTimeImmutable $updatedAt = null,
    ) {
    }
}
