<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Integration;

use PhpToTs\PhpToTsGenerator;
use PhpToTs\Tests\Fixtures\ConstructorParamArrayDTO;
use PhpToTs\Tests\Fixtures\NestedDependencyDTO;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

/**
 * Tests for PHPDoc @param array types in constructor parameters
 */
class ConstructorParamArrayTest extends TestCase
{
    use MatchesSnapshots;

    private PhpToTsGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new PhpToTsGenerator();
    }

    public function testConstructorParamArrayTypes(): void
    {
        // When generating TypeScript for DTO with @param array types
        $typescript = $this->generator->generate(ConstructorParamArrayDTO::class);

        // Then it should properly parse array<Type> syntax
        $this->assertMatchesSnapshot($typescript);

        // Verify specific types are correct
        $this->assertStringContainsString('addresses: AddressDTO[]', $typescript);
        $this->assertStringContainsString('scores: Record<string, number>', $typescript);
        $this->assertStringContainsString('tags: string[] | null', $typescript);

        // Should NOT contain any[]
        $this->assertStringNotContainsString('any[]', $typescript);
    }

    public function testNestedDependenciesGeneration(): void
    {
        // When generating with dependencies
        $files = $this->generator->generateWithDependencies(NestedDependencyDTO::class);

        // Then it should generate all nested classes
        $this->assertArrayHasKey('NestedDependencyDTO', $files);
        $this->assertArrayHasKey('AddressDTO', $files);
        $this->assertArrayHasKey('UserDTO', $files);
        $this->assertArrayHasKey('RoleEnum', $files);

        // Check that main file has proper array types
        $mainFile = $files['NestedDependencyDTO'];
        $this->assertStringContainsString('addresses: AddressDTO[]', $mainFile);
        $this->assertStringContainsString('users: UserDTO[]', $mainFile);
    }

    public function testNestedDependenciesSnapshot(): void
    {
        // When generating with dependencies
        $files = $this->generator->generateWithDependencies(NestedDependencyDTO::class);

        // Then all files should match snapshots
        $this->assertMatchesSnapshot($files);
    }
}
