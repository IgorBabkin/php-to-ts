<?php

declare(strict_types=1);

namespace PhpToTs\Command;

use PhpToTs\PhpToTsGenerator;
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
                'with-dependencies',
                'd',
                InputOption::VALUE_NONE,
                'Generate dependencies as well'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $source = $input->getArgument('source');
        $outputDir = $input->getOption('output');
        $withDependencies = $input->getOption('with-dependencies');

        if (!file_exists($source)) {
            $io->error("Source path '{$source}' does not exist");
            return Command::FAILURE;
        }

        // Create output directory if it doesn't exist
        if (!is_dir($outputDir) && !mkdir($outputDir, 0755, true)) {
            $io->error("Failed to create output directory '{$outputDir}'");
            return Command::FAILURE;
        }

        $generator = new PhpToTsGenerator();
        $phpFiles = $this->findPhpFiles($source);

        if (empty($phpFiles)) {
            $io->warning('No PHP files found');
            return Command::SUCCESS;
        }

        $io->title('PHP to TypeScript Generator');
        $io->progressStart(count($phpFiles));

        $generated = 0;
        $errors = [];

        foreach ($phpFiles as $file) {
            try {
                $classes = $this->extractClassesFromFile($file);

                foreach ($classes as $className) {
                    if ($withDependencies) {
                        $files = $generator->generateWithDependencies($className);
                        foreach ($files as $name => $typescript) {
                            $this->writeTypeScriptFile($outputDir, $name, $typescript);
                            $generated++;
                        }
                    } else {
                        $typescript = $generator->generate($className);
                        $shortName = (new \ReflectionClass($className))->getShortName();
                        $this->writeTypeScriptFile($outputDir, $shortName, $typescript);
                        $generated++;
                    }
                }
            } catch (\Throwable $e) {
                $errors[] = "Error processing {$file}: {$e->getMessage()}";
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

    private function writeTypeScriptFile(string $outputDir, string $className, string $content): void
    {
        $filename = $outputDir . '/' . $className . '.ts';
        file_put_contents($filename, $content);
    }
}
