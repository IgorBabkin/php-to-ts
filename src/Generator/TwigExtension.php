<?php

declare(strict_types=1);

namespace PhpToTs\Generator;

use PhpToTs\Analyzer\ComplexArrayTypeParser;
use PhpToTs\Analyzer\PropertyInfo;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension for TypeScript generation
 * Moves type mapping logic from PHP to Twig templates
 */
class TwigExtension extends AbstractExtension
{
    private TypeMapper $typeMapper;
    private ComplexArrayTypeParser $complexArrayParser;

    public function __construct()
    {
        $this->typeMapper = new TypeMapper();
        $this->complexArrayParser = new ComplexArrayTypeParser();
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('tsType', [$this, 'toTypeScriptType']),
            new TwigFilter('cleanDocComment', [$this, 'cleanDocComment']),
        ];
    }

    /**
     * Convert PropertyInfo to TypeScript type string
     */
    public function toTypeScriptType(PropertyInfo $property): string
    {
        // Check for complex array types first (array{...} or array<...>)
        $complexArrayType = $property->getComplexArrayType();
        if ($complexArrayType !== null && $this->complexArrayParser->isComplexArrayType($complexArrayType)) {
            $tsType = $this->complexArrayParser->parse($complexArrayType);

            // Apply nullable if needed
            if ($property->isNullable()) {
                $tsType = $this->typeMapper->mapNullableType($tsType);
            }

            return $tsType;
        }

        // Fall back to regular type mapping
        $tsType = $this->typeMapper->mapType(
            $property->getType(),
            $property->getArrayItemType()
        );

        if ($property->isNullable()) {
            $tsType = $this->typeMapper->mapNullableType($tsType);
        }

        return $tsType;
    }

    /**
     * Clean PHPDoc comment for TypeScript (simplified version for enums)
     */
    public function cleanDocComment(string $docComment): string
    {
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

        return implode("\n", $cleaned);
    }
}
