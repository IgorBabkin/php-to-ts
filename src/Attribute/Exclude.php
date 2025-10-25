<?php

declare(strict_types=1);

namespace PhpToTs\Attribute;

use Attribute;

/**
 * Marks a property to be excluded from TypeScript generation
 * Can be applied to class properties or constructor parameters
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Exclude
{
}
