<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Fixtures;

/**
 * DTO with nested dependencies in constructor params
 */
class NestedDependencyDTO
{
    /**
     * @param array<AddressDTO> $addresses
     * @param array<UserDTO> $users
     */
    public function __construct(
        public readonly string $name,
        public readonly AddressDTO $primaryAddress,
        public readonly RoleEnum $role,
        public readonly array $addresses,
        public readonly array $users,
    ) {
    }
}
