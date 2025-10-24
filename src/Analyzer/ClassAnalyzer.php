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

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $type = $property->getType();
            $propertyType = null;
            $isNullable = false;
            $arrayItemType = null;

            if ($type instanceof ReflectionNamedType) {
                $propertyType = $type->getName();
                $isNullable = $type->allowsNull() && $propertyType !== 'mixed';

                // Check for array type in docblock
                if ($propertyType === 'array') {
                    $arrayItemType = $this->extractArrayItemType($property->getDocComment());
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

            $properties[] = new PropertyInfo(
                name: $property->getName(),
                type: $propertyType,
                isNullable: $isNullable,
                isReadonly: $property->isReadOnly(),
                arrayItemType: $arrayItemType,
                docComment: $this->extractDocComment($property->getDocComment()),
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

        // Remove /** and */ and clean up
        $lines = explode("\n", $docComment);
        $cleaned = [];

        foreach ($lines as $line) {
            $line = trim($line);
            $line = preg_replace('/^\*+\s?/', '', $line);
            $line = trim($line, '/* ');

            if (!empty($line) && !str_starts_with($line, '@')) {
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

        // Look for @var Type[] pattern
        if (preg_match('/@var\s+([a-zA-Z0-9_\\\\]+)\[\]/', $docComment, $matches)) {
            return $this->extractShortClassName($matches[1]);
        }

        return null;
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
}
