<?php

declare(strict_types=1);

namespace PhpToTs\Analyzer;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;

/**
 * Analyzes PHP classes and extracts structure information
 */
class ClassAnalyzer
{
    public function analyze(string $className): ClassInfo
    {
        $reflection = new ReflectionClass($className);

        if ($reflection->isEnum()) {
            return $this->analyzeEnum($reflection);
        }

        $properties = $this->analyzeProperties($reflection);
        $dependencies = $this->extractDependencies($properties);

        return new ClassInfo(
            className: $reflection->getShortName(),
            namespace: $reflection->getNamespaceName(),
            properties: $properties,
            dependencies: $dependencies,
            docComment: $this->extractDocComment($reflection->getDocComment()),
            isEnum: false,
        );
    }

    /**
     * @return PropertyInfo[]
     */
    private function analyzeProperties(ReflectionClass $reflection): array
    {
        $properties = [];

        // Get constructor docblock for @param tags
        $constructor = $reflection->getConstructor();
        $constructorDocComment = $constructor ? $constructor->getDocComment() : false;

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            // Skip properties marked with #[Exclude] attribute
            if ($this->hasExcludeAttribute($property)) {
                continue;
            }

            $type = $property->getType();
            $propertyType = null;
            $isNullable = false;
            $arrayItemType = null;

            if ($type instanceof ReflectionNamedType) {
                $propertyType = $type->getName();
                $isNullable = $type->allowsNull() && $propertyType !== 'mixed';

                // Check for array type in docblock (both property and constructor @param)
                if ($propertyType === 'array') {
                    $propertyDocComment = $property->getDocComment();
                    $paramDocComment = $this->extractParamDocComment($constructorDocComment, $property->getName());

                    // Try property docblock first, then constructor @param
                    $docComment = $propertyDocComment !== false ? $propertyDocComment : $paramDocComment;
                    $arrayItemType = $this->extractArrayItemType($docComment);
                }
            } elseif ($type instanceof ReflectionUnionType) {
                $types = $type->getTypes();
                $nonNullTypes = array_filter($types, fn($t) => $t->getName() !== 'null');

                if (count($nonNullTypes) === 1) {
                    $propertyType = reset($nonNullTypes)->getName();
                    $isNullable = true;
                } else {
                    // Handle union types - for now, use first non-null type
                    $propertyType = reset($nonNullTypes)->getName();
                }
            }

            if ($propertyType === null) {
                $propertyType = 'mixed';
            }

            // Extract complex array type from PHPDoc if present
            $complexArrayType = null;
            if ($propertyType === 'array') {
                $propertyDocComment = $property->getDocComment();
                $paramDocComment = $this->extractParamDocComment($constructorDocComment, $property->getName());

                // Try property docblock first, then constructor @param
                $docComment = $propertyDocComment !== false ? $propertyDocComment : $paramDocComment;
                $complexArrayType = $this->extractComplexArrayType($docComment);
            }

            $properties[] = new PropertyInfo(
                name: $property->getName(),
                type: $propertyType,
                isNullable: $isNullable,
                isReadonly: $property->isReadOnly(),
                arrayItemType: $arrayItemType,
                docComment: $this->extractDocComment($property->getDocComment()),
                complexArrayType: $complexArrayType,
            );
        }

        return $properties;
    }

    /**
     * @param PropertyInfo[] $properties
     * @return string[]
     */
    private function extractDependencies(array $properties): array
    {
        $dependencies = [];

        foreach ($properties as $property) {
            $type = $property->getType();

            // Check if it's a custom class (not a built-in type)
            if ($this->isCustomClass($type)) {
                $dependencies[] = $this->extractShortClassName($type);
            }

            // Check array item type
            if ($property->getArrayItemType() && $this->isCustomClass($property->getArrayItemType())) {
                $dependencies[] = $this->extractShortClassName($property->getArrayItemType());
            }

            // Check complex array types for class references
            if ($property->getComplexArrayType()) {
                $complexType = $property->getComplexArrayType();
                $classNames = $this->extractClassNamesFromComplexType($complexType);
                foreach ($classNames as $className) {
                    $dependencies[] = $className;
                }
            }
        }

        return array_unique($dependencies);
    }

    private function isCustomClass(string $type): bool
    {
        $builtInTypes = ['string', 'int', 'float', 'bool', 'array', 'mixed', 'null', 'void', 'object'];

        if (in_array($type, $builtInTypes, true)) {
            return false;
        }

        // Check if it's DateTime
        if (str_contains($type, 'DateTime')) {
            return false;
        }

        return true;
    }

    private function extractShortClassName(string $fullClassName): string
    {
        $parts = explode('\\', $fullClassName);
        return end($parts);
    }

    private function extractDocComment(string|false $docComment): ?string
    {
        if ($docComment === false) {
            return null;
        }

        // Tags that are useful in TypeScript/TSDoc
        $preservedTags = ['@deprecated', '@see', '@link', '@example', '@var'];

        // Remove /** and */ and clean up
        $lines = explode("\n", $docComment);
        $cleaned = [];

        foreach ($lines as $line) {
            $line = trim($line);
            $line = preg_replace('/^\*+\s?/', '', $line);
            $line = trim($line, '/* ');

            if (empty($line)) {
                continue;
            }

            // Keep line if it's not a tag, or if it's a preserved tag
            $isPreservedTag = false;
            foreach ($preservedTags as $tag) {
                if (str_starts_with($line, $tag)) {
                    $isPreservedTag = true;
                    break;
                }
            }

            if (!str_starts_with($line, '@') || $isPreservedTag) {
                $cleaned[] = $line;
            }
        }

        return implode("\n", $cleaned) ?: null;
    }

    private function extractArrayItemType(string|false $docComment): ?string
    {
        if ($docComment === false) {
            return null;
        }

        // Look for @var or @param Type[] pattern
        if (preg_match('/(?:@var|@param)\s+([a-zA-Z0-9_\\\\]+)\[\]/', $docComment, $matches)) {
            return $this->extractShortClassName($matches[1]);
        }

        // Look for @var or @param array<Type> pattern (single type generic)
        if (preg_match('/(?:@var|@param)\s+array\s*<\s*([a-zA-Z0-9_\\\\]+)\s*>/', $docComment, $matches)) {
            return $this->extractShortClassName($matches[1]);
        }

        return null;
    }

    /**
     * Extract complex array type from PHPDoc
     * Returns full type string for array{...} or array<...> patterns
     * Looks for both @var and @param tags
     */
    private function extractComplexArrayType(string|false $docComment): ?string
    {
        if ($docComment === false) {
            return null;
        }

        // Look for @var or @param with array{...} or array<...>
        // Need to handle nested structures, so count braces/brackets
        if (preg_match('/(?:@var|@param)\s+(array\s*[{<])/', $docComment, $matches, PREG_OFFSET_CAPTURE)) {
            $start = $matches[1][1];
            $openChar = $matches[1][0][strlen($matches[1][0]) - 1]; // { or <
            $closeChar = $openChar === '{' ? '}' : '>';

            $depth = 1;
            $length = strlen($docComment);
            $i = $start + strlen($matches[1][0]);

            while ($i < $length && $depth > 0) {
                $char = $docComment[$i];
                if ($char === $openChar) {
                    $depth++;
                } elseif ($char === $closeChar) {
                    $depth--;
                } elseif ($char === "\n" && $depth === 0) {
                    break;
                }
                $i++;
            }

            if ($depth === 0) {
                return trim(substr($docComment, $start, $i - $start));
            }
        }

        return null;
    }

    /**
     * Extract @param docblock for a specific parameter from constructor docblock
     */
    private function extractParamDocComment(string|false $constructorDoc, string $paramName): string|false
    {
        if ($constructorDoc === false) {
            return false;
        }

        // Look for @param <type> $paramName
        // Type can be complex like array<string, int> or array{id: int, name: string}
        // Pattern: @param followed by type (which may contain <>, {}, |, spaces), then $paramName
        if (preg_match('/@param\s+([^\$\n]+?)\s+\$' . preg_quote($paramName, '/') . '(?:\s|$)/m', $constructorDoc, $matches)) {
            $type = trim($matches[1]);
            return '@param ' . $type . ' $' . $paramName;
        }

        return false;
    }

    private function analyzeEnum(ReflectionClass $reflection): ClassInfo
    {
        return new ClassInfo(
            className: $reflection->getShortName(),
            namespace: $reflection->getNamespaceName(),
            properties: [],
            dependencies: [],
            docComment: $this->extractDocComment($reflection->getDocComment()),
            isEnum: true,
        );
    }

    /**
     * Check if property has #[Exclude] attribute
     */
    private function hasExcludeAttribute(ReflectionProperty $property): bool
    {
        $attributes = $property->getAttributes(\PhpToTs\Attribute\Exclude::class);
        return !empty($attributes);
    }

    /**
     * Extract class names from complex array types
     * Examples:
     * - array<UserDTO> → ['UserDTO']
     * - array<string, AddressDTO> → ['AddressDTO']
     * - array{user: UserDTO, address: AddressDTO} → ['UserDTO', 'AddressDTO']
     *
     * @return string[]
     */
    private function extractClassNamesFromComplexType(string $complexType): array
    {
        $classNames = [];

        // For array<Type> or array<Key, Type>, extract class names
        if (preg_match('/array\s*<([^>]+)>/', $complexType, $matches)) {
            $content = $matches[1];
            // Split by comma, check each part
            $parts = array_map('trim', explode(',', $content));
            foreach ($parts as $part) {
                // Remove union types (e.g., "Type|null" → "Type")
                $part = preg_replace('/\s*\|\s*null\s*$/', '', $part);
                if ($this->isCustomClass($part)) {
                    $classNames[] = $this->extractShortClassName($part);
                }
            }
        }

        // For array{key: Type, ...}, extract class names from values
        if (preg_match('/array\s*\{([^}]+)\}/', $complexType, $matches)) {
            $content = $matches[1];
            // Match field definitions: "name: Type" or "name?: Type"
            if (preg_match_all('/[a-zA-Z0-9_]+\??\s*:\s*([a-zA-Z0-9_\\\\|]+)/', $content, $fieldMatches)) {
                foreach ($fieldMatches[1] as $fieldType) {
                    // Remove union types
                    $types = explode('|', $fieldType);
                    foreach ($types as $type) {
                        $type = trim($type);
                        if ($type !== 'null' && $this->isCustomClass($type)) {
                            $classNames[] = $this->extractShortClassName($type);
                        }
                    }
                }
            }
        }

        return array_unique($classNames);
    }
}
