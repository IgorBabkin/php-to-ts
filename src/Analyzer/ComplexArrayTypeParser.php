<?php

declare(strict_types=1);

namespace PhpToTs\Analyzer;

use PhpToTs\Generator\TypeMapper;

/**
 * Parses complex PHPDoc array types:
 * - array{foo: int, bar: string} (shaped arrays)
 * - array<string, int> (generic arrays)
 * - array<string> (generic single-type arrays)
 */
class ComplexArrayTypeParser
{
    private TypeMapper $typeMapper;

    public function __construct()
    {
        $this->typeMapper = new TypeMapper();
    }

    /**
     * Check if type is a complex array type
     */
    public function isComplexArrayType(string $type): bool
    {
        return $this->isShapedArray($type) || $this->isGenericArray($type);
    }

    /**
     * Parse complex array type to TypeScript
     */
    public function parse(string $type): string
    {
        if ($this->isShapedArray($type)) {
            return $this->parseShapedArray($type);
        }

        if ($this->isGenericArray($type)) {
            return $this->parseGenericArray($type);
        }

        return 'any';
    }

    /**
     * Check if type is shaped array: array{...}
     */
    private function isShapedArray(string $type): bool
    {
        return preg_match('/^array\s*\{/', $type) === 1;
    }

    /**
     * Check if type is generic array: array<...>
     */
    private function isGenericArray(string $type): bool
    {
        return preg_match('/^array\s*</', $type) === 1;
    }

    /**
     * Parse shaped array: array{id: int, name: string} -> { id: number; name: string }
     */
    private function parseShapedArray(string $type): string
    {
        // Extract content between { and }
        if (!preg_match('/array\s*\{([^}]+)\}/', $type, $matches)) {
            return 'any';
        }

        $content = $matches[1];
        $fields = $this->splitFields($content);
        $tsFields = [];

        foreach ($fields as $field) {
            $field = trim($field);
            if (empty($field)) {
                continue;
            }

            // Handle optional fields: name?: string
            $isOptional = str_contains($field, '?:');
            $field = str_replace('?:', ':', $field);

            // Split by colon
            $parts = explode(':', $field, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $fieldName = trim($parts[0]);
            $fieldType = trim($parts[1]);

            // Check if field name itself is optional (some docs use name?: instead of name:?)
            if (str_ends_with($fieldName, '?')) {
                $isOptional = true;
                $fieldName = rtrim($fieldName, '?');
            }

            // Parse the field type (might be nullable, might be nested array)
            $tsType = $this->parseFieldType($fieldType);

            $optionalMarker = $isOptional ? '?' : '';
            $tsFields[] = "{$fieldName}{$optionalMarker}: {$tsType}";
        }

        return '{ ' . implode('; ', $tsFields) . ' }';
    }

    /**
     * Parse generic array: array<K, V> or array<T>
     */
    private function parseGenericArray(string $type): string
    {
        // Extract content between < and >
        if (!preg_match('/array\s*<([^>]+)>/', $type, $matches)) {
            return 'any[]';
        }

        $content = $matches[1];
        $types = array_map('trim', explode(',', $content));

        if (count($types) === 1) {
            // array<string> -> string[]
            $itemType = $this->typeMapper->mapType($types[0]);
            return "{$itemType}[]";
        } elseif (count($types) === 2) {
            // array<string, int> -> Record<string, number>
            $keyType = $this->typeMapper->mapType($types[0]);
            $valueType = $this->typeMapper->mapType($types[1]);
            return "Record<{$keyType}, {$valueType}>";
        }

        return 'any[]';
    }

    /**
     * Parse field type (handles nullable, nested arrays, etc.)
     */
    private function parseFieldType(string $type): string
    {
        // Handle nullable: string|null or ?string
        $isNullable = false;
        if (str_starts_with($type, '?')) {
            $isNullable = true;
            $type = substr($type, 1);
        } elseif (str_contains($type, '|null') || str_contains($type, 'null|')) {
            $isNullable = true;
            $type = str_replace(['|null', 'null|'], '', $type);
            $type = trim($type, '|');
        }

        // Handle nested shaped arrays
        if ($this->isShapedArray($type)) {
            $tsType = $this->parseShapedArray($type);
        }
        // Handle generic arrays
        elseif ($this->isGenericArray($type)) {
            $tsType = $this->parseGenericArray($type);
        }
        // Regular type mapping
        else {
            $tsType = $this->typeMapper->mapType($type);
        }

        return $isNullable ? "{$tsType} | null" : $tsType;
    }

    /**
     * Split fields respecting nested structures
     */
    private function splitFields(string $content): array
    {
        $fields = [];
        $current = '';
        $depth = 0;

        for ($i = 0; $i < strlen($content); $i++) {
            $char = $content[$i];

            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;
            } elseif ($char === ',' && $depth === 0) {
                $fields[] = $current;
                $current = '';
                continue;
            }

            $current .= $char;
        }

        if (!empty($current)) {
            $fields[] = $current;
        }

        return $fields;
    }
}
