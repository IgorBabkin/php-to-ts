<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Fixtures;

/**
 * DTO with shaped array types
 */
class ShapedArrayDTO
{
    /**
     * Simple shaped array
     * @var array{id: int, name: string, active: bool}
     */
    public array $user;

    /**
     * Shaped array with nullable fields
     * @var array{id: int, email: string|null, phone: ?string}
     */
    public array $contact;

    /**
     * Shaped array with optional fields
     * @var array{id: int, name?: string, email?: string}
     */
    public array $profile;

    /**
     * Nested shaped arrays
     * @var array{user: array{id: int, name: string}, meta: array{created: string}}
     */
    public array $data;
}
