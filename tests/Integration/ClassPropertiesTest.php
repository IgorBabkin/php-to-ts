<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Integration;

use PhpToTs\PhpToTsGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Tests for class properties (not constructor properties) generation
 */
class ClassPropertiesTest extends TestCase
{
    private PhpToTsGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new PhpToTsGenerator();
    }

    public function testGenerateClassWithRegularProperties(): void
    {
        // Given a class with regular class properties (not constructor properties)
        $php = <<<'PHP'
<?php
namespace Test;
class UserData {
    public int $id;
    public string $name;
    public float $balance;
    public bool $isActive;
}
PHP;

        // Create temporary class
        eval(str_replace('<?php', '', $php));

        // When generating TypeScript
        $typescript = $this->generator->generate('Test\UserData');

        // Then it should include all properties
        $this->assertStringContainsString('export interface UserData', $typescript);
        $this->assertStringContainsString('id: number', $typescript);
        $this->assertStringContainsString('name: string', $typescript);
        $this->assertStringContainsString('balance: number', $typescript);
        $this->assertStringContainsString('isActive: boolean', $typescript);
    }

    public function testGenerateClassWithPropertiesWithoutTypeHints(): void
    {
        // Given a class with properties without type hints
        $php = <<<'PHP'
<?php
namespace Test;
class LegacyData {
    public $id;
    public $name;
    public int $age;
}
PHP;

        eval(str_replace('<?php', '', $php));

        // When generating TypeScript
        $typescript = $this->generator->generate('Test\LegacyData');

        // Then properties without types should be 'any'
        $this->assertStringContainsString('id: any', $typescript);
        $this->assertStringContainsString('name: any', $typescript);
        $this->assertStringContainsString('age: number', $typescript);
    }

    public function testGenerateClassOmitsMethods(): void
    {
        // Given a class with properties AND methods
        $php = <<<'PHP'
<?php
namespace Test;
class DataWithMethods {
    public int $value;

    public function getValue(): int {
        return $this->value;
    }

    public function setValue(int $value): void {
        $this->value = $value;
    }

    public function calculate(): float {
        return $this->value * 1.5;
    }
}
PHP;

        eval(str_replace('<?php', '', $php));

        // When generating TypeScript
        $typescript = $this->generator->generate('Test\DataWithMethods');

        // Then it should include properties
        $this->assertStringContainsString('value: number', $typescript);

        // But NOT include methods
        $this->assertStringNotContainsString('getValue', $typescript);
        $this->assertStringNotContainsString('setValue', $typescript);
        $this->assertStringNotContainsString('calculate', $typescript);
        $this->assertStringNotContainsString('function', $typescript);
    }

    public function testGenerateClassWithNullableProperties(): void
    {
        // Given a class with nullable properties
        $php = <<<'PHP'
<?php
namespace Test;
class NullableData {
    public ?int $id;
    public ?string $name;
    public int $required;
}
PHP;

        eval(str_replace('<?php', '', $php));

        // When generating TypeScript
        $typescript = $this->generator->generate('Test\NullableData');

        // Then nullable properties should have | null
        $this->assertStringContainsString('id: number | null', $typescript);
        $this->assertStringContainsString('name: string | null', $typescript);
        $this->assertStringContainsString('required: number', $typescript);
        $this->assertStringNotContainsString('required: number | null', $typescript);
    }

    public function testGenerateClassWithArrayProperties(): void
    {
        // Given a class with array properties
        $php = <<<'PHP'
<?php
namespace Test;
class ArrayData {
    /**
     * @var string[]
     */
    public array $tags;

    /**
     * @var int[]|null
     */
    public ?array $scores;

    public array $mixed;
}
PHP;

        eval(str_replace('<?php', '', $php));

        // When generating TypeScript
        $typescript = $this->generator->generate('Test\ArrayData');

        // Then arrays should be properly typed
        $this->assertStringContainsString('tags: string[]', $typescript);
        $this->assertStringContainsString('scores: number[] | null', $typescript);
        $this->assertStringContainsString('mixed: any[]', $typescript);
    }

    public function testGenerateEnumClass(): void
    {
        // Given a PHP enum
        $php = <<<'PHP'
<?php
namespace Test;
enum StatusEnum: string {
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DELETED = 'deleted';
}
PHP;

        eval(str_replace('<?php', '', $php));

        // When generating TypeScript
        $typescript = $this->generator->generate('Test\StatusEnum');

        // Then it should generate a TypeScript enum
        $this->assertStringContainsString('export enum StatusEnum', $typescript);
        $this->assertStringContainsString("ACTIVE = 'active'", $typescript);
        $this->assertStringContainsString("INACTIVE = 'inactive'", $typescript);
        $this->assertStringContainsString("DELETED = 'deleted'", $typescript);

        // And NOT an interface
        $this->assertStringNotContainsString('export interface', $typescript);
    }

    public function testGenerateIntEnumClass(): void
    {
        // Given a PHP enum with int backing
        $php = <<<'PHP'
<?php
namespace Test;
enum PriorityEnum: int {
    case LOW = 1;
    case MEDIUM = 2;
    case HIGH = 3;
}
PHP;

        eval(str_replace('<?php', '', $php));

        // When generating TypeScript
        $typescript = $this->generator->generate('Test\PriorityEnum');

        // Then it should generate a TypeScript enum with numbers
        $this->assertStringContainsString('export enum PriorityEnum', $typescript);
        $this->assertStringContainsString('LOW = 1', $typescript);
        $this->assertStringContainsString('MEDIUM = 2', $typescript);
        $this->assertStringContainsString('HIGH = 3', $typescript);
    }

    public function testGenerateClassWithEnumProperty(): void
    {
        // Given an enum and a class using it
        $php = <<<'PHP'
<?php
namespace Test;
enum UserStatusEnum: string {
    case ACTIVE = 'active';
    case BANNED = 'banned';
}
class UserWithEnum {
    public int $id;
    public UserStatusEnum $status;
}
PHP;

        eval(str_replace('<?php', '', $php));

        // When generating with dependencies
        $files = $this->generator->generateWithDependencies('Test\UserWithEnum');

        // Then it should generate both files
        $this->assertArrayHasKey('UserWithEnum', $files);
        $this->assertArrayHasKey('UserStatusEnum', $files);

        // And the class should import the enum
        $this->assertStringContainsString("import { UserStatusEnum } from './UserStatusEnum'", $files['UserWithEnum']);
        $this->assertStringContainsString('status: UserStatusEnum', $files['UserWithEnum']);

        // And the enum should be a TypeScript enum
        $this->assertStringContainsString('export enum UserStatusEnum', $files['UserStatusEnum']);
    }
}
