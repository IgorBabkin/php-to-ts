<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Unit;

use PhpToTs\Analyzer\ClassAnalyzer;
use PhpToTs\Tests\Fixtures\SimpleDTO;
use PhpToTs\Tests\Fixtures\UserDTO;
use PhpToTs\Tests\Fixtures\CollectionDTO;
use PHPUnit\Framework\TestCase;

class ClassAnalyzerTest extends TestCase
{
    private ClassAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new ClassAnalyzer();
    }

    public function testAnalyzeSimpleClass(): void
    {
        $result = $this->analyzer->analyze(SimpleDTO::class);

        $this->assertEquals('SimpleDTO', $result->getClassName());
        $this->assertEquals('PhpToTs\Tests\Fixtures', $result->getNamespace());
        $this->assertCount(5, $result->getProperties());
    }

    public function testAnalyzeProperties(): void
    {
        $result = $this->analyzer->analyze(SimpleDTO::class);
        $properties = $result->getProperties();

        $nameProperty = $properties[0];
        $this->assertEquals('name', $nameProperty->getName());
        $this->assertEquals('string', $nameProperty->getType());
        $this->assertFalse($nameProperty->isNullable());
        $this->assertTrue($nameProperty->isReadonly());

        $emailProperty = $properties[4];
        $this->assertEquals('email', $emailProperty->getName());
        $this->assertEquals('string', $emailProperty->getType());
        $this->assertTrue($emailProperty->isNullable());
    }

    public function testAnalyzeNestedClass(): void
    {
        $result = $this->analyzer->analyze(UserDTO::class);
        $dependencies = $result->getDependencies();

        $this->assertContains('AddressDTO', $dependencies);
    }

    public function testAnalyzeArrayProperties(): void
    {
        $result = $this->analyzer->analyze(CollectionDTO::class);
        $properties = $result->getProperties();

        $tagsProperty = $properties[0];
        $this->assertEquals('tags', $tagsProperty->getName());
        $this->assertEquals('array', $tagsProperty->getType());
        $this->assertEquals('string', $tagsProperty->getArrayItemType());
    }

    public function testExtractDocComment(): void
    {
        $result = $this->analyzer->analyze(SimpleDTO::class);

        $this->assertStringContainsString('Simple user data transfer object', $result->getDocComment());
    }
}
