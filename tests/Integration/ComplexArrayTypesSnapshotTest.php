<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Integration;

use PhpToTs\PhpToTsGenerator;
use PhpToTs\Tests\Fixtures\ShapedArrayDTO;
use PhpToTs\Tests\Fixtures\GenericArrayDTO;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

/**
 * Snapshot tests for complex PHPDoc array types using fixtures
 */
class ComplexArrayTypesSnapshotTest extends TestCase
{
    use MatchesSnapshots;

    private PhpToTsGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new PhpToTsGenerator();
    }

    public function testShapedArrayDTOSnapshot(): void
    {
        // When generating TypeScript for ShapedArrayDTO
        $typescript = $this->generator->generate(ShapedArrayDTO::class);

        // Then it should match the snapshot
        $this->assertMatchesSnapshot($typescript);
    }

    public function testGenericArrayDTOSnapshot(): void
    {
        // When generating TypeScript for GenericArrayDTO
        $typescript = $this->generator->generate(GenericArrayDTO::class);

        // Then it should match the snapshot
        $this->assertMatchesSnapshot($typescript);
    }

    public function testShapedArrayWithDependenciesSnapshot(): void
    {
        // When generating with dependencies
        $files = $this->generator->generateWithDependencies(ShapedArrayDTO::class);

        // Then it should match the snapshot
        $this->assertMatchesSnapshot($files);
    }
}
