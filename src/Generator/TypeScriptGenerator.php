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

    public function __construct(
        private readonly bool $addTsExtensionToImports = false
    ) {
        $loader = new FilesystemLoader(__DIR__ . '/../Template');
        $this->twig = new Environment($loader, [
            'autoescape' => false,
        ]);
        $this->twig->addExtension(new TwigExtension());
    }

    public function generateInterface(ClassInfo $classInfo): string
    {
        // Dependencies now contain full class names, extract short names for imports
        $imports = array_map(
            fn($fullClassName) => $this->extractShortClassName($fullClassName),
            $classInfo->getDependencies()
        );

        return $this->twig->render('interface.twig', [
            'className' => $classInfo->getClassName(),
            'properties' => $classInfo->getProperties(),
            'imports' => array_values(array_unique($imports)),
            'docComment' => $classInfo->getDocComment(),
            'addTsExtensionToImports' => $this->addTsExtensionToImports,
        ]);
    }

    /**
     * Extract short class name from full qualified class name
     */
    private function extractShortClassName(string $fullClassName): string
    {
        $parts = explode('\\', $fullClassName);
        return end($parts);
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

        // Determine if backing type is string (should quote) or int (no quotes)
        $backingType = $reflection->getBackingType();
        $isStringEnum = $backingType && $backingType->getName() === 'string';

        return $this->twig->render('enum.twig', [
            'className' => $reflection->getShortName(),
            'cases' => $cases,
            'docComment' => $reflection->getDocComment() ?: null,
            'isStringEnum' => $isStringEnum,
            'addTsExtensionToImports' => $this->addTsExtensionToImports,
        ]);
    }

}
