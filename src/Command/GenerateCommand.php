<?php

declare(strict_types=1);

namespace PhpToTs\Command;

use PhpToTs\PhpToTsGenerator;
use PhpToTs\Resolver\NamespaceResolver;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * CLI command to generate TypeScript from PHP classes
 */
class GenerateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('generate')
            ->setDescription('Generate TypeScript interfaces from PHP DTO classes')
            ->addArgument(
                'source',
                InputArgument::REQUIRED,
                'Source directory or file containing PHP classes'
            )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_REQUIRED,
                'Output directory for TypeScript files',
                './types'
            )
            ->addOption(
                'no-dependencies',
                null,
                InputOption::VALUE_NONE,
                'Do not generate nested class dependencies (default is to generate them)'
            )
            ->addOption(
                'add-ts-extension-to-imports',
                null,
                InputOption::VALUE_NONE,
                'Add .ts extension to import paths (e.g., "./User.ts" instead of "./User")'
            )
            ->addOption(
                'base-dir',
                'b',
                InputOption::VALUE_REQUIRED,
                'Base directory for PSR-4 namespace resolution (e.g., "src"). When provided, source is treated as namespace pattern'
            )
            ->addOption(
                'namespace-prefix',
                'p',
                InputOption::VALUE_REQUIRED,
                'Namespace prefix to remove when mapping to file path (e.g., "PhpToTs\Tests" for baseDir "tests")'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $source = $input->getArgument('source');
        $outputDir = $input->getOption('output');
        $noDependencies = $input->getOption('no-dependencies');
        $generateDependencies = !$noDependencies; // Generate dependencies by default
        $addTsExtensionToImports = (bool) $input->getOption('add-ts-extension-to-imports');
        $baseDir = $input->getOption('base-dir');
        $namespacePrefix = $input->getOption('namespace-prefix');

        // Create output directory if it doesn't exist
        if (!is_dir($outputDir) && !mkdir($outputDir, 0755, true)) {
            $io->error("Failed to create output directory '{$outputDir}'");
            return Command::FAILURE;
        }

        $generator = new PhpToTsGenerator(addTsExtensionToImports: $addTsExtensionToImports);

        // Determine if using namespace-based or file-based input
        $classes = [];
        if ($baseDir !== null) {
            // Namespace-based input (PSR-4)
            try {
                $classes = $this->resolveNamespacePattern($source, $baseDir, $namespacePrefix, $io);
            } catch (\Throwable $e) {
                $io->error("Error resolving namespace pattern: {$e->getMessage()}");
                return Command::FAILURE;
            }

            if (empty($classes)) {
                $io->warning('No classes found for namespace pattern: ' . $source);
                return Command::SUCCESS;
            }
        } else {
            // File-based input (original behavior)
            if (!file_exists($source)) {
                $io->error("Source path '{$source}' does not exist");
                return Command::FAILURE;
            }

            $phpFiles = $this->findPhpFiles($source);

            if (empty($phpFiles)) {
                $io->warning('No PHP files found');
                return Command::SUCCESS;
            }

            // Extract classes from files
            foreach ($phpFiles as $file) {
                try {
                    $fileClasses = $this->extractClassesFromFile($file);
                    $classes = array_merge($classes, $fileClasses);
                } catch (\Throwable $e) {
                    $io->warning("Error processing {$file}: {$e->getMessage()}");
                }
            }
        }

        if (empty($classes)) {
            $io->warning('No classes found');
            return Command::SUCCESS;
        }

        $io->title('PHP to TypeScript Generator');
        $io->progressStart(count($classes));

        $generated = 0;
        $errors = [];
        $processedClasses = []; // Track already processed classes to avoid duplicates in single run

        foreach ($classes as $className) {
            try {
                if ($generateDependencies) {
                    $files = $generator->generateWithDependencies($className);
                    foreach ($files as $name => $typescript) {
                        // Skip if already processed in this run
                        if (isset($processedClasses[$name])) {
                            continue;
                        }

                        $this->writeTypeScriptFile($outputDir, $name, $typescript);
                        $generated++;
                        $processedClasses[$name] = true;
                    }
                } else {
                    $typescript = $generator->generate($className);
                    $shortName = (new \ReflectionClass($className))->getShortName();

                    if (isset($processedClasses[$shortName])) {
                        continue;
                    }

                    $this->writeTypeScriptFile($outputDir, $shortName, $typescript);
                    $generated++;
                    $processedClasses[$shortName] = true;
                }
            } catch (\Throwable $e) {
                $errors[] = "Error processing {$className}: {$e->getMessage()}";
            }

            $io->progressAdvance();
        }

        $io->progressFinish();

        if ($generated > 0) {
            $io->success("Generated {$generated} TypeScript file(s) in {$outputDir}");
        }

        if (!empty($errors)) {
            $io->warning('Some files had errors:');
            $io->listing($errors);
        }

        return Command::SUCCESS;
    }

    /**
     * @return string[]
     */
    private function findPhpFiles(string $path): array
    {
        if (is_file($path)) {
            return [$path];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path)
        );
        $phpFiles = new RegexIterator($iterator, '/^.+\.php$/i');

        foreach ($phpFiles as $file) {
            $files[] = $file->getPathname();
        }

        return $files;
    }

    /**
     * @return string[]
     */
    private function extractClassesFromFile(string $file): array
    {
        $content = file_get_contents($file);
        $classes = [];

        // Extract namespace
        if (preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch)) {
            $namespace = $namespaceMatch[1];

            // Extract class names
            if (preg_match_all('/(?:class|enum)\s+(\w+)/', $content, $classMatches)) {
                foreach ($classMatches[1] as $className) {
                    $fullClassName = $namespace . '\\' . $className;
                    if (class_exists($fullClassName) || enum_exists($fullClassName)) {
                        $classes[] = $fullClassName;
                    }
                }
            }
        }

        return $classes;
    }

    /**
     * Write TypeScript file to disk
     */
    private function writeTypeScriptFile(string $outputDir, string $className, string $content): void
    {
        $filename = $outputDir . '/' . $className . '.ts';
        file_put_contents($filename, $content);
    }

    /**
     * Resolve namespace pattern to list of class names using PSR-4
     *
     * @return string[] List of fully qualified class names
     */
    private function resolveNamespacePattern(string $namespacePattern, string $baseDir, ?string $namespacePrefix, SymfonyStyle $io): array
    {
        if ($namespacePrefix) {
            $io->note("Resolving namespace pattern: {$namespacePattern} with base dir: {$baseDir} and prefix: {$namespacePrefix}");
        } else {
            $io->note("Resolving namespace pattern: {$namespacePattern} with base dir: {$baseDir}");
        }

        $resolver = new NamespaceResolver($baseDir, $namespacePrefix);
        $classes = $resolver->resolve($namespacePattern);

        $io->note(sprintf("Found %d class(es)", count($classes)));

        return $classes;
    }
}
