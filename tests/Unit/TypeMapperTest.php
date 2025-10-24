<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Unit;

use PhpToTs\Generator\TypeMapper;
use PHPUnit\Framework\TestCase;

class TypeMapperTest extends TestCase
{
    private TypeMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new TypeMapper();
    }

    public function testMapString(): void
    {
        $this->assertEquals('string', $this->mapper->mapType('string'));
    }

    public function testMapInt(): void
    {
        $this->assertEquals('number', $this->mapper->mapType('int'));
    }

    public function testMapFloat(): void
    {
        $this->assertEquals('number', $this->mapper->mapType('float'));
    }

    public function testMapBool(): void
    {
        $this->assertEquals('boolean', $this->mapper->mapType('bool'));
    }

    public function testMapArray(): void
    {
        $this->assertEquals('any[]', $this->mapper->mapType('array'));
    }

    public function testMapTypedArray(): void
    {
        $this->assertEquals('string[]', $this->mapper->mapType('array', 'string'));
    }

    public function testMapDateTime(): void
    {
        $result = $this->mapper->mapType('DateTimeImmutable');
        $this->assertContains($result, ['Date', 'string']);
    }

    public function testMapCustomClass(): void
    {
        $this->assertEquals('AddressDTO', $this->mapper->mapType('AddressDTO'));
    }

    public function testMapNullableType(): void
    {
        $this->assertEquals('string | null', $this->mapper->mapNullableType('string'));
    }

    public function testMapMixed(): void
    {
        $this->assertEquals('any', $this->mapper->mapType('mixed'));
    }
}
