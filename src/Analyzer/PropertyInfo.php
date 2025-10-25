<?php

declare(strict_types=1);

namespace PhpToTs\Analyzer;

/**
 * Holds information about a class property
 */
class PropertyInfo
{
    public function __construct(
        private readonly string $name,
        private readonly string $type,
        private readonly bool $isNullable,
        private readonly bool $isReadonly,
        private readonly ?string $arrayItemType = null,
        private readonly ?string $docComment = null,
        private readonly ?string $complexArrayType = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    public function isReadonly(): bool
    {
        return $this->isReadonly;
    }

    public function getArrayItemType(): ?string
    {
        return $this->arrayItemType;
    }

    public function getDocComment(): ?string
    {
        return $this->docComment;
    }

    public function getComplexArrayType(): ?string
    {
        return $this->complexArrayType;
    }
}
