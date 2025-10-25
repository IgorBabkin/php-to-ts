<?php

declare(strict_types=1);

namespace PhpToTs\Tests\Fixtures;

/**
 * Task with priority and role
 */
class TaskDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly PriorityEnum $priority,
        public readonly RoleEnum $assignedRole,
    ) {}

    /**
     * This method should NOT appear in TypeScript
     */
    public function getDescription(): string
    {
        return "Task #{$this->id}: {$this->title}";
    }

    /**
     * This method should NOT appear in TypeScript
     */
    public function isHighPriority(): bool
    {
        return $this->priority === PriorityEnum::HIGH || $this->priority === PriorityEnum::CRITICAL;
    }
}
