<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Integration;

use PhpToTs\PhpToTsGenerator;
use PhpToTs\Tests\Fixtures\ProjectDTO;
use PhpToTs\Tests\Fixtures\TaskDTO;
use PhpToTs\Tests\Fixtures\RoleEnum;
use PhpToTs\Tests\Fixtures\PriorityEnum;
use PHPUnit\Framework\TestCase;

/**
 * Comprehensive test for:
 * - Enums (string and int)
 * - Nested classes
 * - Nested classes with enums
 * - Regular class properties (not constructor properties)
 * - Methods omission (should NOT be in TypeScript)
 */
class ComprehensiveGenerationTest extends TestCase
{
    private PhpToTsGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new PhpToTsGenerator();
    }

    public function testGenerateProjectWithAllFeatures(): void
    {
        // When generating ProjectDTO with all dependencies
        $files = $this->generator->generateWithDependencies(ProjectDTO::class);

        // Then it should generate 4 files total
        $this->assertCount(4, $files, 'Should generate ProjectDTO + TaskDTO + RoleEnum + PriorityEnum');

        // Verify all expected files exist
        $this->assertArrayHasKey('ProjectDTO', $files);
        $this->assertArrayHasKey('TaskDTO', $files);
        $this->assertArrayHasKey('RoleEnum', $files);
        $this->assertArrayHasKey('PriorityEnum', $files);
    }

    public function testProjectDTOStructure(): void
    {
        $files = $this->generator->generateWithDependencies(ProjectDTO::class);
        $projectTS = $files['ProjectDTO'];

        // Should be an interface
        $this->assertStringContainsString('export interface ProjectDTO', $projectTS);

        // Should have imports for dependencies
        $this->assertStringContainsString("import { TaskDTO } from './TaskDTO'", $projectTS);
        $this->assertStringContainsString("import { RoleEnum } from './RoleEnum'", $projectTS);
        $this->assertStringContainsString("import { PriorityEnum } from './PriorityEnum'", $projectTS);

        // Should include all class properties
        $this->assertStringContainsString('id: number', $projectTS);
        $this->assertStringContainsString('name: string', $projectTS);
        $this->assertStringContainsString('description: string | null', $projectTS);
        $this->assertStringContainsString('tasks: TaskDTO[]', $projectTS);
        $this->assertStringContainsString('ownerRole: RoleEnum', $projectTS);
        $this->assertStringContainsString('defaultPriority: PriorityEnum', $projectTS);

        // Should NOT include methods
        $this->assertStringNotContainsString('addTask', $projectTS);
        $this->assertStringNotContainsString('getTaskCount', $projectTS);
        $this->assertStringNotContainsString('hasHighPriorityTasks', $projectTS);
        $this->assertStringNotContainsString('internalCalculation', $projectTS);
        $this->assertStringNotContainsString('function', $projectTS);
        $this->assertStringNotContainsString('=>', $projectTS);
    }

    public function testTaskDTOStructure(): void
    {
        $files = $this->generator->generateWithDependencies(ProjectDTO::class);
        $taskTS = $files['TaskDTO'];

        // Should be an interface
        $this->assertStringContainsString('export interface TaskDTO', $taskTS);

        // Should have imports for enums
        $this->assertStringContainsString("import { PriorityEnum } from './PriorityEnum'", $taskTS);
        $this->assertStringContainsString("import { RoleEnum } from './RoleEnum'", $taskTS);

        // Should include properties
        $this->assertStringContainsString('id: number', $taskTS);
        $this->assertStringContainsString('title: string', $taskTS);
        $this->assertStringContainsString('priority: PriorityEnum', $taskTS);
        $this->assertStringContainsString('assignedRole: RoleEnum', $taskTS);

        // Should NOT include methods
        $this->assertStringNotContainsString('getDescription', $taskTS);
        $this->assertStringNotContainsString('isHighPriority', $taskTS);
        $this->assertStringNotContainsString('function', $taskTS);
    }

    public function testRoleEnumStructure(): void
    {
        $files = $this->generator->generateWithDependencies(ProjectDTO::class);
        $roleTS = $files['RoleEnum'];

        // Should be a TypeScript enum (not interface)
        $this->assertStringContainsString('export enum RoleEnum', $roleTS);
        $this->assertStringNotContainsString('export interface', $roleTS);

        // Should have string values with quotes
        $this->assertStringContainsString("ADMIN = 'admin'", $roleTS);
        $this->assertStringContainsString("USER = 'user'", $roleTS);
        $this->assertStringContainsString("GUEST = 'guest'", $roleTS);
    }

    public function testPriorityEnumStructure(): void
    {
        $files = $this->generator->generateWithDependencies(ProjectDTO::class);
        $priorityTS = $files['PriorityEnum'];

        // Should be a TypeScript enum (not interface)
        $this->assertStringContainsString('export enum PriorityEnum', $priorityTS);
        $this->assertStringNotContainsString('export interface', $priorityTS);

        // Should have numeric values WITHOUT quotes
        $this->assertStringContainsString('LOW = 1', $priorityTS);
        $this->assertStringContainsString('MEDIUM = 2', $priorityTS);
        $this->assertStringContainsString('HIGH = 3', $priorityTS);
        $this->assertStringContainsString('CRITICAL = 4', $priorityTS);

        // Should NOT have quotes around numbers
        $this->assertStringNotContainsString("LOW = '1'", $priorityTS);
        $this->assertStringNotContainsString("MEDIUM = '2'", $priorityTS);
    }

    public function testDocCommentsPreserved(): void
    {
        $files = $this->generator->generateWithDependencies(ProjectDTO::class);

        // Check that doc comments are preserved
        $this->assertStringContainsString('Project with tasks and metadata', $files['ProjectDTO']);
        $this->assertStringContainsString('Task with priority and role', $files['TaskDTO']);
        $this->assertStringContainsString('User role enumeration', $files['RoleEnum']);
        $this->assertStringContainsString('Priority levels with integer backing', $files['PriorityEnum']);
    }

    public function testGenerateTaskDTODirectly(): void
    {
        // When generating TaskDTO directly with dependencies
        $files = $this->generator->generateWithDependencies(TaskDTO::class);

        // Then it should generate TaskDTO + both enums
        $this->assertCount(3, $files);
        $this->assertArrayHasKey('TaskDTO', $files);
        $this->assertArrayHasKey('PriorityEnum', $files);
        $this->assertArrayHasKey('RoleEnum', $files);
    }

    public function testGenerateEnumDirectly(): void
    {
        // When generating an enum directly
        $typescript = $this->generator->generate(RoleEnum::class);

        // Then it should generate a TypeScript enum
        $this->assertStringContainsString('export enum RoleEnum', $typescript);
        $this->assertStringContainsString("ADMIN = 'admin'", $typescript);
    }

    public function testNoCircularDependencies(): void
    {
        // When generating with complex nested dependencies
        $files = $this->generator->generateWithDependencies(ProjectDTO::class);

        // Then it should not hang or error (circular dependency check)
        $this->assertIsArray($files);
        $this->assertNotEmpty($files);
    }
}
