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

    public function __construct(
        private readonly bool $addTsExtensionToImports = false
    ) {
        $this->analyzer = new ClassAnalyzer();
        $this->generator = new TypeScriptGenerator($addTsExtensionToImports);
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
                // Dependencies now contain full class names with namespaces
                foreach ($classInfo->getDependencies() as $dependencyFullClass) {
                    // Check if class/enum exists
                    if ((class_exists($dependencyFullClass) || enum_exists($dependencyFullClass))
                        && !isset($processed[$dependencyFullClass])) {
                        $toProcess[] = $dependencyFullClass;
                    }
                }
            }

            $processed[$currentClass] = true;
        }

        return $result;
    }
}
