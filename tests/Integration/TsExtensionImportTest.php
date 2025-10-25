<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Integration;

use PhpToTs\PhpToTsGenerator;
use PhpToTs\Tests\Fixtures\NestedDependencyDTO;
use PhpToTs\Tests\Fixtures\UserDTO;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

/**
 * Tests for .ts extension in imports flag
 */
class TsExtensionImportTest extends TestCase
{
    use MatchesSnapshots;

    public function testImportsWithoutTsExtension(): void
    {
        // Given: Generator without .ts extension flag
        $generator = new PhpToTsGenerator();

        // When: Generating TypeScript for class with imports
        $typescript = $generator->generate(UserDTO::class);

        // Then: Imports should NOT have .ts extension
        $this->assertStringContainsString("import { AddressDTO } from './AddressDTO';", $typescript);
        $this->assertStringNotContainsString("import { AddressDTO } from './AddressDTO.ts';", $typescript);
    }

    public function testImportsWithTsExtension(): void
    {
        // Given: Generator WITH .ts extension flag enabled
        $generator = new PhpToTsGenerator(addTsExtensionToImports: true);

        // When: Generating TypeScript for class with imports
        $typescript = $generator->generate(UserDTO::class);

        // Then: Imports should have .ts extension
        $this->assertStringContainsString("import { AddressDTO } from './AddressDTO.ts';", $typescript);
        $this->assertStringNotContainsString("import { AddressDTO } from './AddressDTO';", $typescript);
    }

    public function testMultipleImportsWithTsExtension(): void
    {
        // Given: Generator WITH .ts extension flag enabled
        $generator = new PhpToTsGenerator(addTsExtensionToImports: true);

        // When: Generating TypeScript for class with multiple imports
        $typescript = $generator->generate(NestedDependencyDTO::class);

        // Then: All imports should have .ts extension
        $this->assertStringContainsString("import { AddressDTO } from './AddressDTO.ts';", $typescript);
        $this->assertStringContainsString("import { RoleEnum } from './RoleEnum.ts';", $typescript);
        $this->assertStringContainsString("import { UserDTO } from './UserDTO.ts';", $typescript);
    }

    public function testSnapshotWithTsExtension(): void
    {
        // Given: Generator WITH .ts extension flag enabled
        $generator = new PhpToTsGenerator(addTsExtensionToImports: true);

        // When: Generating TypeScript
        $typescript = $generator->generate(NestedDependencyDTO::class);

        // Then: Should match snapshot
        $this->assertMatchesSnapshot($typescript);
    }

    public function testGenerateWithDependenciesAndTsExtension(): void
    {
        // Given: Generator WITH .ts extension flag enabled
        $generator = new PhpToTsGenerator(addTsExtensionToImports: true);

        // When: Generating with dependencies
        $files = $generator->generateWithDependencies(UserDTO::class);

        // Then: All files should have .ts extension in imports
        $mainFile = $files['UserDTO'];
        $this->assertStringContainsString("import { AddressDTO } from './AddressDTO.ts';", $mainFile);
        $this->assertMatchesSnapshot($files);
    }
}
