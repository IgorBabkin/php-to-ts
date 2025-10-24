<?php

declare(strict_types=1);

namespace PhpToTs\Generator;

/**
 * Maps PHP types to TypeScript types
 */
class TypeMapper
{
    private const TYPE_MAP = [
        'string' => 'string',
        'int' => 'number',
        'integer' => 'number',
        'float' => 'number',
        'double' => 'number',
        'bool' => 'boolean',
        'boolean' => 'boolean',
        'array' => 'any[]',
        'mixed' => 'any',
        'object' => 'any',
        'void' => 'void',
        'null' => 'null',
    ];

    /**
     * Map a PHP type to TypeScript type
     */
    public function mapType(string $phpType, ?string $arrayItemType = null): string
    {
        // Handle DateTime types
        if (str_contains($phpType, 'DateTime')) {
            return 'string'; // or 'Date' depending on preference
        }

        // Handle array with known item type
        if ($phpType === 'array' && $arrayItemType !== null) {
            $mappedItemType = $this->mapType($arrayItemType);
            return $mappedItemType . '[]';
        }

        // Check if it's a built-in type
        if (isset(self::TYPE_MAP[$phpType])) {
            return self::TYPE_MAP[$phpType];
        }

        // Must be a custom class - extract short class name
        return $this->extractShortClassName($phpType);
    }

    /**
     * Extract short class name from fully qualified class name
     */
    private function extractShortClassName(string $fullClassName): string
    {
        $parts = explode('\\', $fullClassName);
        return end($parts);
    }

    /**
     * Wrap a type with nullable syntax
     */
    public function mapNullableType(string $tsType): string
    {
        if ($tsType === 'any') {
            return 'any';
        }

        return $tsType . ' | null';
    }
}
