<?php

declare(strict_types=1);

namespace PhpToTs\Generator;

use PhpToTs\Analyzer\ClassInfo;
use PhpToTs\Analyzer\PropertyInfo;
use ReflectionEnum;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Generates TypeScript code from analyzed PHP classes
 */
class TypeScriptGenerator
{
    private Environment $twig;
    private TypeMapper $typeMapper;

    public function __construct()
    {
        $loader = new FilesystemLoader(__DIR__ . '/../Template');
        $this->twig = new Environment($loader, [
            'autoescape' => false,
        ]);
        $this->typeMapper = new TypeMapper();
    }

    public function generateInterface(ClassInfo $classInfo): string
    {
        $properties = $this->prepareProperties($classInfo->getProperties());
        $imports = $this->extractImports($classInfo->getDependencies());

        return $this->twig->render('interface.twig', [
            'className' => $classInfo->getClassName(),
            'properties' => $properties,
            'imports' => $imports,
            'docComment' => $classInfo->getDocComment(),
        ]);
    }

    public function generateEnum(string $enumClass): string
    {
        $reflection = new ReflectionEnum($enumClass);
        $cases = [];

        foreach ($reflection->getCases() as $case) {
            $cases[] = [
                'name' => $case->getName(),
                'value' => $case->getBackingValue(),
            ];
        }

        $docComment = $reflection->getDocComment();
        $cleanDocComment = $docComment ? $this->cleanDocComment($docComment) : null;

        return $this->twig->render('enum.twig', [
            'className' => $reflection->getShortName(),
            'cases' => $cases,
            'docComment' => $cleanDocComment,
        ]);
    }

    /**
     * @param PropertyInfo[] $properties
     * @return array<string, mixed>[]
     */
    private function prepareProperties(array $properties): array
    {
        $prepared = [];

        foreach ($properties as $property) {
            $tsType = $this->typeMapper->mapType(
                $property->getType(),
                $property->getArrayItemType()
            );

            if ($property->isNullable()) {
                $tsType = $this->typeMapper->mapNullableType($tsType);
            }

            $prepared[] = [
                'name' => $property->getName(),
                'typeScriptType' => $tsType,
                'docComment' => $property->getDocComment(),
            ];
        }

        return $prepared;
    }

    /**
     * @param string[] $dependencies
     * @return string[]
     */
    private function extractImports(array $dependencies): array
    {
        return array_values(array_unique($dependencies));
    }

    private function cleanDocComment(string $docComment): string
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
