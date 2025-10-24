<?php

declare(strict_types=1);

namespace PhpToTs\Analyzer;

/**
 * Holds information about an analyzed class
 */
class ClassInfo
{
    /**
     * @param PropertyInfo[] $properties
     * @param string[] $dependencies
     */
    public function __construct(
        private readonly string $className,
        private readonly string $namespace,
        private readonly array $properties,
        private readonly array $dependencies = [],
        private readonly ?string $docComment = null,
        private readonly bool $isEnum = false,
    ) {
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @return PropertyInfo[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function getDocComment(): ?string
    {
        return $this->docComment;
    }

    public function isEnum(): bool
    {
        return $this->isEnum;
    }
}
