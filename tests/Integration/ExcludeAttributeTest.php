<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Integration;

use PhpToTs\PhpToTsGenerator;
use PhpToTs\Tests\Fixtures\ExcludeAttributeDTO;
use PhpToTs\Tests\Fixtures\ExcludeClassPropertyDTO;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

/**
 * Tests for #[Exclude] attribute functionality using snapshots
 */
class ExcludeAttributeTest extends TestCase
{
    use MatchesSnapshots;

    private PhpToTsGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new PhpToTsGenerator();
    }

    public function testExcludeConstructorParametersSnapshot(): void
    {
        // When generating TypeScript for DTO with excluded constructor params
        $typescript = $this->generator->generate(ExcludeAttributeDTO::class);

        // Then it should match the snapshot (excluded properties removed)
        $this->assertMatchesSnapshot($typescript);
    }

    public function testExcludeRegularClassPropertiesSnapshot(): void
    {
        // When generating TypeScript for DTO with excluded class properties
        $typescript = $this->generator->generate(ExcludeClassPropertyDTO::class);

        // Then it should match the snapshot (excluded properties removed)
        $this->assertMatchesSnapshot($typescript);
    }

    public function testExcludeWithDependenciesSnapshot(): void
    {
        // When generating with dependencies
        $files = $this->generator->generateWithDependencies(ExcludeAttributeDTO::class);

        // Then it should match the snapshot
        $this->assertMatchesSnapshot($files);
    }
}
