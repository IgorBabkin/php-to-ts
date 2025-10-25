<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Fixtures;

use PhpToTs\Attribute\Exclude;

/**
 * DTO with excluded regular class properties
 */
class ExcludeClassPropertyDTO
{
    public int $id;
    public string $name;

    /**
     * Internal tracking field - should be excluded
     */
    #[Exclude]
    public string $internalState;

    public string $email;

    /**
     * Sensitive data - should be excluded
     */
    #[Exclude]
    public ?array $auditLog = null;

    public bool $isActive = true;
}
