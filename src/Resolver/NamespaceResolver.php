<?php

declare(strict_types=1);

namespace PhpToTs\Resolver;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

/**
 * Resolves namespace patterns to file paths using PSR-4 autoloading standard
 */
class NamespaceResolver
{
    public function __construct(
        private readonly string $baseDir,
        private readonly ?string $namespacePrefix = null
    ) {
        if (!is_dir($baseDir)) {
            throw new \InvalidArgumentException("Base directory does not exist: {$baseDir}");
        }
    }

    /**
     * Resolve namespace pattern to list of fully qualified class names
     *
     * @param string $namespacePattern e.g., "\LMS\View\*" or "\LMS\View\TariffDTO"
     * @return string[] List of fully qualified class names
     */
    public function resolve(string $namespacePattern): array
    {
        // Normalize namespace (remove leading backslash)
        $namespacePattern = ltrim($namespacePattern, '\\');

        // Check if it's a glob pattern or specific class
        if (str_contains($namespacePattern, '*')) {
            return $this->resolveGlobPattern($namespacePattern);
        }

        return $this->resolveSpecificClass($namespacePattern);
    }

    /**
     * Resolve a specific class namespace to file path
     *
     * @param string $namespace e.g., "LMS\View\TariffDTO"
     * @return string[] Single class name in array
     */
    private function resolveSpecificClass(string $namespace): array
    {
        $filePath = $this->namespaceToFilePath($namespace);

        if (!file_exists($filePath)) {
            throw new \RuntimeException("Class file not found: {$filePath} for namespace: {$namespace}");
        }

        // Extract the class name from the file
        $className = $this->extractClassNameFromFile($filePath);

        if ($className === null) {
            throw new \RuntimeException("No class found in file: {$filePath}");
        }

        return [$className];
    }

    /**
     * Resolve glob pattern to list of class names
     *
     * @param string $pattern e.g., "LMS\View\*" or "LMS\View\Tariff\*"
     * @return string[] List of fully qualified class names
     */
    private function resolveGlobPattern(string $pattern): array
    {
        // Split pattern into base namespace and glob part
        $parts = explode('*', $pattern, 2);
        $baseNamespace = rtrim($parts[0], '\\');
        $globSuffix = $parts[1] ?? '';

        // Convert namespace to directory path
        $searchDir = $this->namespaceToDirectoryPath($baseNamespace);

        if (!is_dir($searchDir)) {
            return []; // No matching directory
        }

        // Determine search depth based on glob pattern
        if ($globSuffix === '') {
            // Pattern: "LMS\View\*" - search only direct children
            return $this->findClassesInDirectory($searchDir, $baseNamespace, false);
        } elseif ($globSuffix === '\*' || $globSuffix === '\\*') {
            // Pattern: "LMS\View\*\*" - search recursively
            return $this->findClassesInDirectory($searchDir, $baseNamespace, true);
        }

        return [];
    }

    /**
     * Convert namespace to file path (PSR-4)
     * Example: "LMS\View\TariffDTO" => "src/LMS/View/TariffDTO.php"
     * With prefix "PhpToTs\Tests\" and baseDir "tests": "PhpToTs\Tests\Fixtures\User" => "tests/Fixtures/User.php"
     */
    private function namespaceToFilePath(string $namespace): string
    {
        $relativeNamespace = $this->removeNamespacePrefix($namespace);
        $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativeNamespace) . '.php';
        return $this->baseDir . DIRECTORY_SEPARATOR . $relativePath;
    }

    /**
     * Convert namespace to directory path (PSR-4)
     * Example: "LMS\View" => "src/LMS/View"
     * With prefix: "PhpToTs\Tests\Fixtures" => "tests/Fixtures"
     */
    private function namespaceToDirectoryPath(string $namespace): string
    {
        $relativeNamespace = $this->removeNamespacePrefix($namespace);
        $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativeNamespace);
        return $this->baseDir . DIRECTORY_SEPARATOR . $relativePath;
    }

    /**
     * Remove namespace prefix from a namespace
     * Example: namespace="PhpToTs\Tests\Fixtures\User", prefix="PhpToTs\Tests\" => "Fixtures\User"
     */
    private function removeNamespacePrefix(string $namespace): string
    {
        if ($this->namespacePrefix === null) {
            return $namespace;
        }

        $prefix = rtrim($this->namespacePrefix, '\\') . '\\';
        if (str_starts_with($namespace, $prefix)) {
            return substr($namespace, strlen($prefix));
        }

        return $namespace;
    }

    /**
     * Find all PHP classes in a directory
     *
     * @param string $directory Directory to search
     * @param string $baseNamespace Base namespace for the directory
     * @param bool $recursive Whether to search recursively
     * @return string[] List of fully qualified class names
     */
    private function findClassesInDirectory(string $directory, string $baseNamespace, bool $recursive): array
    {
        $classes = [];

        if ($recursive) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            $files = new RegexIterator($iterator, '/\.php$/');
        } else {
            $files = glob($directory . DIRECTORY_SEPARATOR . '*.php') ?: [];
        }

        foreach ($files as $file) {
            $filePath = is_string($file) ? $file : $file->getPathname();
            $className = $this->extractClassNameFromFile($filePath);

            if ($className !== null) {
                $classes[] = $className;
            }
        }

        return $classes;
    }

    /**
     * Extract fully qualified class name from PHP file
     *
     * @param string $filePath Path to PHP file
     * @return string|null Fully qualified class name or null if not found
     */
    private function extractClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        $namespace = null;
        $className = null;

        // Extract namespace
        if (preg_match('/^\s*namespace\s+([a-zA-Z0-9_\\\\]+)\s*;/m', $content, $matches)) {
            $namespace = $matches[1];
        }

        // Extract class/interface/enum/trait name
        if (preg_match('/^\s*(?:abstract\s+)?(?:final\s+)?(?:class|interface|enum|trait)\s+([a-zA-Z0-9_]+)/m', $content, $matches)) {
            $className = $matches[1];
        }

        if ($className === null) {
            return null;
        }

        return $namespace ? $namespace . '\\' . $className : $className;
    }
}
