<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Integration;

use PhpToTs\PhpToTsGenerator;
use PhpToTs\Tests\Fixtures\SimpleDTO;
use PhpToTs\Tests\Fixtures\UserDTO;
use PhpToTs\Tests\Fixtures\UserWithFullAddressDTO;
use PhpToTs\Tests\Fixtures\CollectionDTO;
use PhpToTs\Tests\Fixtures\UserWithStatusDTO;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class GenerationTest extends TestCase
{
    use MatchesSnapshots;

    private PhpToTsGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new PhpToTsGenerator();
    }

    public function testGenerateSimpleDTO(): void
    {
        $typescript = $this->generator->generate(SimpleDTO::class);

        $this->assertMatchesSnapshot($typescript);
        $this->assertStringContainsString('export interface SimpleDTO', $typescript);
        $this->assertStringContainsString('name: string', $typescript);
        $this->assertStringContainsString('age: number', $typescript);
        $this->assertStringContainsString('balance: number', $typescript);
        $this->assertStringContainsString('isActive: boolean', $typescript);
        $this->assertStringContainsString('email: string | null', $typescript);
    }

    public function testGenerateNestedDTO(): void
    {
        $typescript = $this->generator->generate(UserDTO::class);

        $this->assertStringContainsString('export interface UserDTO', $typescript);
        $this->assertStringContainsString('address: AddressDTO', $typescript);
        $this->assertStringContainsString('billingAddress: AddressDTO | null', $typescript);
        $this->assertStringContainsString("import { AddressDTO } from './AddressDTO'", $typescript);
    }

    public function testGenerateDeeplyNestedDTO(): void
    {
        $typescript = $this->generator->generate(UserWithFullAddressDTO::class);

        $this->assertStringContainsString('export interface UserWithFullAddressDTO', $typescript);
        $this->assertStringContainsString('address: AddressWithCityDTO', $typescript);
        $this->assertStringContainsString("import { AddressWithCityDTO } from './AddressWithCityDTO'", $typescript);
    }

    public function testGenerateCollectionDTO(): void
    {
        $typescript = $this->generator->generate(CollectionDTO::class);

        $this->assertStringContainsString('export interface CollectionDTO', $typescript);
        $this->assertStringContainsString('tags: string[]', $typescript);
        $this->assertStringContainsString('addresses: AddressDTO[]', $typescript);
        $this->assertStringContainsString('metadata: any[]', $typescript);
        $this->assertStringContainsString('scores: number[] | null', $typescript);
    }

    public function testGenerateDTOWithEnum(): void
    {
        $typescript = $this->generator->generate(UserWithStatusDTO::class);

        $this->assertStringContainsString('export interface UserWithStatusDTO', $typescript);
        $this->assertStringContainsString('status: UserStatus', $typescript);
        $this->assertStringContainsString("import { UserStatus } from './UserStatus'", $typescript);
    }

    public function testGenerateEnum(): void
    {
        $typescript = $this->generator->generateEnum(\PhpToTs\Tests\Fixtures\UserStatus::class);

        $this->assertStringContainsString('export enum UserStatus', $typescript);
        $this->assertStringContainsString("ACTIVE = 'active'", $typescript);
        $this->assertStringContainsString("INACTIVE = 'inactive'", $typescript);
        $this->assertStringContainsString("SUSPENDED = 'suspended'", $typescript);
        $this->assertStringContainsString("DELETED = 'deleted'", $typescript);
    }

    public function testGenerateWithJSDoc(): void
    {
        $typescript = $this->generator->generate(SimpleDTO::class);

        $this->assertStringContainsString('/**', $typescript);
        $this->assertStringContainsString('* Simple user data transfer object', $typescript);
        $this->assertStringContainsString('*/', $typescript);
    }

    public function testGenerateMultipleFiles(): void
    {
        $files = $this->generator->generateWithDependencies(UserDTO::class);

        $this->assertArrayHasKey('UserDTO', $files);
        $this->assertArrayHasKey('AddressDTO', $files);
    }
}
