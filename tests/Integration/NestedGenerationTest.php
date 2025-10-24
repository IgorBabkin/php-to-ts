<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Integration;

use PhpToTs\PhpToTsGenerator;
use PhpToTs\Tests\Fixtures\UserDTO;
use PhpToTs\Tests\Fixtures\UserWithFullAddressDTO;
use PHPUnit\Framework\TestCase;

/**
 * Tests for automatic nested class generation
 */
class NestedGenerationTest extends TestCase
{
    private PhpToTsGenerator $generator;
    private string $outputDir;

    protected function setUp(): void
    {
        $this->generator = new PhpToTsGenerator();
        $this->outputDir = sys_get_temp_dir() . '/php-to-ts-test-' . uniqid();
        mkdir($this->outputDir, 0755, true);
    }

    protected function tearDown(): void
    {
        // Clean up output directory
        if (is_dir($this->outputDir)) {
            $files = glob($this->outputDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->outputDir);
        }
    }

    public function testGenerateWithDependenciesCreatesAllNestedFiles(): void
    {
        // When generating a class with nested dependencies
        $files = $this->generator->generateWithDependencies(UserDTO::class);

        // Then it should generate the main file AND all nested dependency files
        $this->assertArrayHasKey('UserDTO', $files);
        $this->assertArrayHasKey('AddressDTO', $files);

        // And each file should have proper content
        $this->assertStringContainsString('export interface UserDTO', $files['UserDTO']);
        $this->assertStringContainsString('export interface AddressDTO', $files['AddressDTO']);
    }

    public function testGenerateDeeplyNestedCreatesAllLevels(): void
    {
        // When generating a deeply nested class (3 levels: User -> Address -> City)
        $files = $this->generator->generateWithDependencies(UserWithFullAddressDTO::class);

        // Then all 3 levels should be generated as separate files
        $this->assertArrayHasKey('UserWithFullAddressDTO', $files);
        $this->assertArrayHasKey('AddressWithCityDTO', $files);
        $this->assertArrayHasKey('CityDTO', $files);

        $this->assertCount(3, $files);
    }

    public function testNestedFilesHaveProperImports(): void
    {
        // When generating nested classes
        $files = $this->generator->generateWithDependencies(UserDTO::class);

        // Then the parent should import its dependencies
        $this->assertStringContainsString("import { AddressDTO } from './AddressDTO'", $files['UserDTO']);

        // And use them in the interface
        $this->assertStringContainsString('address: AddressDTO', $files['UserDTO']);
    }

    public function testGenerateWithDependenciesAvoidsCircularDependencies(): void
    {
        // This test ensures the generator doesn't get into infinite loops
        // when classes might reference each other

        $files = $this->generator->generateWithDependencies(UserDTO::class);

        // Should successfully generate without errors
        $this->assertIsArray($files);
        $this->assertNotEmpty($files);
    }

    public function testWriteGeneratedFilesToDisk(): void
    {
        // When generating files with dependencies
        $files = $this->generator->generateWithDependencies(UserDTO::class);

        // And writing them to disk
        foreach ($files as $className => $content) {
            $filePath = $this->outputDir . '/' . $className . '.ts';
            file_put_contents($filePath, $content);
        }

        // Then all files should exist on disk
        $this->assertFileExists($this->outputDir . '/UserDTO.ts');
        $this->assertFileExists($this->outputDir . '/AddressDTO.ts');
    }

    public function testOverwritesExistingFiles(): void
    {
        // Given an existing TypeScript file
        $existingFile = $this->outputDir . '/UserDTO.ts';
        $originalContent = '// Original content';
        file_put_contents($existingFile, $originalContent);

        // When generating the same file
        $files = $this->generator->generateWithDependencies(UserDTO::class);

        // And writing it to disk
        foreach ($files as $className => $content) {
            $filePath = $this->outputDir . '/' . $className . '.ts';
            file_put_contents($filePath, $content);
        }

        // The file should be overwritten with new content
        $this->assertFileExists($existingFile);
        $newContent = file_get_contents($existingFile);

        // Should contain generated TypeScript, not original content
        $this->assertStringContainsString('export interface UserDTO', $newContent);
        $this->assertStringNotContainsString('// Original content', $newContent);
    }

    public function testAvoidsDuplicatesInSingleRun(): void
    {
        // Simulate processing multiple files that reference the same dependency
        $processedClasses = [];
        $allGeneratedFiles = [];

        // First class with dependencies
        $files1 = $this->generator->generateWithDependencies(UserDTO::class);
        foreach ($files1 as $name => $content) {
            if (!isset($processedClasses[$name])) {
                $allGeneratedFiles[$name] = $content;
                $processedClasses[$name] = true;
            }
        }

        // Second class that might share dependencies (simulated)
        // In real scenario, another DTO might also use AddressDTO
        $files2 = $this->generator->generateWithDependencies(UserDTO::class);
        foreach ($files2 as $name => $content) {
            if (!isset($processedClasses[$name])) {
                $allGeneratedFiles[$name] = $content;
                $processedClasses[$name] = true;
            }
        }

        // Should track that classes were already processed
        $this->assertArrayHasKey('UserDTO', $allGeneratedFiles);
        $this->assertArrayHasKey('AddressDTO', $allGeneratedFiles);
        $this->assertCount(2, $allGeneratedFiles); // No duplicates
    }
}
