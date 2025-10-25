<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Fixtures;

/**
 * DTO with generic array types
 */
class GenericArrayDTO
{
    /**
     * Generic array with string keys
     * @var array<string, int>
     */
    public array $scores;

    /**
     * Generic array with int keys
     * @var array<int, string>
     */
    public array $names;

    /**
     * Generic array single type (same as T[])
     * @var array<string>
     */
    public array $tags;

    /**
     * Regular array syntax for comparison
     * @var string[]
     */
    public array $simple;
}
