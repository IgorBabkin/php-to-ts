<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests for file writing behavior (will be moved to a FileWriter class)
 */
class FileWriterTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/file-writer-test-' . uniqid();
        mkdir($this->testDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testDir)) {
            $files = glob($this->testDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testDir);
        }
    }

    public function testWriteFileWhenNotExists(): void
    {
        $filePath = $this->testDir . '/test.ts';
        $content = 'export interface Test {}';

        // File should not exist yet
        $this->assertFileDoesNotExist($filePath);

        // Write file
        file_put_contents($filePath, $content);

        // File should now exist with correct content
        $this->assertFileExists($filePath);
        $this->assertEquals($content, file_get_contents($filePath));
    }

    public function testShouldOverwriteExistingFile(): void
    {
        $filePath = $this->testDir . '/test.ts';
        $originalContent = '// Original';
        $newContent = '// New';

        // Create original file
        file_put_contents($filePath, $originalContent);

        // Always write (overwrite existing files)
        file_put_contents($filePath, $newContent);

        // New content should be written
        $this->assertEquals($newContent, file_get_contents($filePath));
    }

    public function testWriteMultipleFiles(): void
    {
        $files = [
            'UserDTO' => 'export interface UserDTO {}',
            'AddressDTO' => 'export interface AddressDTO {}',
            'CityDTO' => 'export interface CityDTO {}',
        ];

        foreach ($files as $name => $content) {
            file_put_contents($this->testDir . '/' . $name . '.ts', $content);
        }

        // All files should exist
        $this->assertFileExists($this->testDir . '/UserDTO.ts');
        $this->assertFileExists($this->testDir . '/AddressDTO.ts');
        $this->assertFileExists($this->testDir . '/CityDTO.ts');
    }

    public function testTrackProcessedClassesInSingleRun(): void
    {
        // Track which classes have been processed in this run to avoid duplicates
        $processedClasses = [];
        $generated = 0;

        $filesToGenerate = [
            'UserDTO' => 'content1',
            'AddressDTO' => 'content2',
            'UserDTO' => 'content1', // Duplicate - should be skipped
        ];

        foreach ($filesToGenerate as $name => $content) {
            // Skip if already processed in this run
            if (isset($processedClasses[$name])) {
                continue;
            }

            $filePath = $this->testDir . '/' . $name . '.ts';
            file_put_contents($filePath, $content);
            $generated++;
            $processedClasses[$name] = true;
        }

        // Should generate only 2 files (UserDTO duplicate was skipped)
        $this->assertCount(2, $processedClasses);
        $this->assertEquals(2, $generated);
        $this->assertFileExists($this->testDir . '/UserDTO.ts');
        $this->assertFileExists($this->testDir . '/AddressDTO.ts');
    }
}
