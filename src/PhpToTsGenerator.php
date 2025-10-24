<?php

declare(strict_types=1);

namespace PhpToTs;

use PhpToTs\Analyzer\ClassAnalyzer;
use PhpToTs\Generator\TypeScriptGenerator;

/**
 * Main entry point for PHP to TypeScript generation
 */
class PhpToTsGenerator
{
    private ClassAnalyzer $analyzer;
    private TypeScriptGenerator $generator;

    public function __construct()
    {
        $this->analyzer = new ClassAnalyzer();
        $this->generator = new TypeScriptGenerator();
    }

    /**
     * Generate TypeScript for a single PHP class
     */
    public function generate(string $phpClass): string
    {
        $classInfo = $this->analyzer->analyze($phpClass);

        if ($classInfo->isEnum()) {
            return $this->generator->generateEnum($phpClass);
        }

        return $this->generator->generateInterface($classInfo);
    }

    /**
     * Generate TypeScript for an enum
     */
    public function generateEnum(string $enumClass): string
    {
        return $this->generator->generateEnum($enumClass);
    }

    /**
     * Generate TypeScript for a class and all its dependencies
     *
     * @return array<string, string> Map of class name to TypeScript code
     */
    public function generateWithDependencies(string $phpClass): array
    {
        $processed = [];
        $toProcess = [$phpClass];
        $result = [];

        while (!empty($toProcess)) {
            $currentClass = array_shift($toProcess);

            if (isset($processed[$currentClass])) {
                continue;
            }

            $classInfo = $this->analyzer->analyze($currentClass);
            $className = $classInfo->getClassName();

            if ($classInfo->isEnum()) {
                $result[$className] = $this->generator->generateEnum($currentClass);
            } else {
                $result[$className] = $this->generator->generateInterface($classInfo);

                // Add dependencies to process queue
                foreach ($classInfo->getDependencies() as $dependency) {
                    // Try to resolve dependency to full class name
                    $dependencyClass = $this->resolveDependencyClass($dependency, $classInfo->getNamespace());
                    if ($dependencyClass && !isset($processed[$dependencyClass])) {
                        $toProcess[] = $dependencyClass;
                    }
                }
            }

            $processed[$currentClass] = true;
        }

        return $result;
    }

    /**
     * Try to resolve a dependency class name
     */
    private function resolveDependencyClass(string $shortClassName, string $namespace): ?string
    {
        // Try same namespace first
        $fullClassName = $namespace . '\\' . $shortClassName;

        if (class_exists($fullClassName) || enum_exists($fullClassName)) {
            return $fullClassName;
        }

        // If not found, return null (could be from a different namespace)
        return null;
    }
}
