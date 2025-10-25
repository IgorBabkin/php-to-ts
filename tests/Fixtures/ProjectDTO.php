<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Fixtures;

/**
 * Project with tasks and metadata
 */
class ProjectDTO
{
    // Regular class properties (not constructor properties)
    public int $id;
    public string $name;
    public ?string $description = null;

    /**
     * @var TaskDTO[]
     */
    public array $tasks = [];

    public RoleEnum $ownerRole = RoleEnum::ADMIN;
    public PriorityEnum $defaultPriority = PriorityEnum::MEDIUM;

    /**
     * This method should NOT appear in TypeScript
     */
    public function addTask(TaskDTO $task): void
    {
        $this->tasks[] = $task;
    }

    /**
     * This method should NOT appear in TypeScript
     */
    public function getTaskCount(): int
    {
        return count($this->tasks);
    }

    /**
     * This method should NOT appear in TypeScript
     */
    public function hasHighPriorityTasks(): bool
    {
        foreach ($this->tasks as $task) {
            if ($task->isHighPriority()) {
                return true;
            }
        }
        return false;
    }

    /**
     * This method should NOT appear in TypeScript
     */
    private function internalCalculation(): float
    {
        return 42.0;
    }
}
