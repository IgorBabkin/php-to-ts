<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Integration;

use PhpToTs\PhpToTsGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Tests for complex PHPDoc array types:
 * - array{foo: int, bar: string} (shaped arrays)
 * - array<string, int> (generic arrays with key-value types)
 */
class ComplexArrayTypesTest extends TestCase
{
    private PhpToTsGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new PhpToTsGenerator();
    }

    public function testShapedArrayWithPrimitives(): void
    {
        // Given a class with shaped array PHPDoc
        $php = <<<'PHP'
<?php
namespace Test;
class DataWithShapedArray {
    /**
     * @var array{id: int, name: string, active: bool}
     */
    public array $user;
}
PHP;

        eval(str_replace('<?php', '', $php));

        // When generating TypeScript
        $typescript = $this->generator->generate('Test\DataWithShapedArray');

        // Then it should generate a TypeScript object type
        $this->assertStringContainsString('user: { id: number; name: string; active: boolean }', $typescript);
    }

    public function testShapedArrayWithNullableFields(): void
    {
        // Given a shaped array with nullable fields
        $php = <<<'PHP'
<?php
namespace Test;
class DataWithNullableFields {
    /**
     * @var array{id: int, email: string|null, phone: ?string}
     */
    public array $contact;
}
PHP;

        eval(str_replace('<?php', '', $php));

        // When generating TypeScript
        $typescript = $this->generator->generate('Test\DataWithNullableFields');

        // Then nullable fields should have | null
        $this->assertStringContainsString('contact: { id: number; email: string | null; phone: string | null }', $typescript);
    }

    public function testShapedArrayWithNestedShapes(): void
    {
        // Given a shaped array with nested shapes
        $php = <<<'PHP'
<?php
namespace Test;
class DataWithNestedShapes {
    /**
     * @var array{user: array{id: int, name: string}, meta: array{created: string}}
     */
    public array $data;
}
PHP;

        eval(str_replace('<?php', '', $php));

        // When generating TypeScript
        $typescript = $this->generator->generate('Test\DataWithNestedShapes');

        // Then it should generate nested object types
        $this->assertStringContainsString('data: { user: { id: number; name: string }; meta: { created: string } }', $typescript);
    }

    public function testGenericArrayWithStringKeys(): void
    {
        // Given a generic array with string keys
        $php = <<<'PHP'
<?php
namespace Test;
class DataWithStringMap {
    /**
     * @var array<string, int>
     */
    public array $scores;
}
PHP;

        eval(str_replace('<?php', '', $php));

        // When generating TypeScript
        $typescript = $this->generator->generate('Test\DataWithStringMap');

        // Then it should generate Record or index signature
        // Accepting either Record<string, number> or { [key: string]: number }
        $this->assertTrue(
            str_contains($typescript, 'scores: Record<string, number>') ||
            str_contains($typescript, 'scores: { [key: string]: number }')
        );
    }

    public function testGenericArrayWithIntKeys(): void
    {
        // Given a generic array with int keys
        $php = <<<'PHP'
<?php
namespace Test;
class DataWithIntMap {
    /**
     * @var array<int, string>
     */
    public array $names;
}
PHP;

        eval(str_replace('<?php', '', $php));

        // When generating TypeScript
        $typescript = $this->generator->generate('Test\DataWithIntMap');

        // Then it should generate Record or index signature with number
        $this->assertTrue(
            str_contains($typescript, 'names: Record<number, string>') ||
            str_contains($typescript, 'names: { [key: number]: string }')
        );
    }

    public function testGenericArraySingleType(): void
    {
        // Given a generic array with single type (array<T> is same as T[])
        $php = <<<'PHP'
<?php
namespace Test;
class DataWithGenericArray {
    /**
     * @var array<string>
     */
    public array $tags;
}
PHP;

        eval(str_replace('<?php', '', $php));

        // When generating TypeScript
        $typescript = $this->generator->generate('Test\DataWithGenericArray');

        // Then it should generate string[]
        $this->assertStringContainsString('tags: string[]', $typescript);
    }

    public function testComplexMixedTypes(): void
    {
        // Given a class with multiple complex array types
        $php = <<<'PHP'
<?php
namespace Test;
class ComplexData {
    /**
     * @var array{id: int, name: string}
     */
    public array $user;

    /**
     * @var array<string, int>
     */
    public array $scores;

    /**
     * @var array<string>
     */
    public array $tags;

    /**
     * @var string[]
     */
    public array $simple;
}
PHP;

        eval(str_replace('<?php', '', $php));

        // When generating TypeScript
        $typescript = $this->generator->generate('Test\ComplexData');

        // Then all types should be correctly generated
        $this->assertStringContainsString('user: { id: number; name: string }', $typescript);
        $this->assertTrue(
            str_contains($typescript, 'scores: Record<string, number>') ||
            str_contains($typescript, 'scores: { [key: string]: number }')
        );
        $this->assertStringContainsString('tags: string[]', $typescript);
        $this->assertStringContainsString('simple: string[]', $typescript);
    }

    public function testShapedArrayWithOptionalFields(): void
    {
        // Given a shaped array with optional fields (using ?)
        $php = <<<'PHP'
<?php
namespace Test;
class DataWithOptionalFields {
    /**
     * @var array{id: int, name?: string, email?: string}
     */
    public array $user;
}
PHP;

        eval(str_replace('<?php', '', $php));

        // When generating TypeScript
        $typescript = $this->generator->generate('Test\DataWithOptionalFields');

        // Then optional fields should have ?
        $this->assertStringContainsString('user: { id: number; name?: string; email?: string }', $typescript);
    }
}
