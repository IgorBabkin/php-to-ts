# Quick Start Guide

Get started with PHP to TypeScript Generator in 5 minutes!

## Installation

```bash
composer require php-to-ts-generator/php-to-ts-generator
```

## Basic Usage

### 1. Create a PHP DTO

```php
<?php
// src/DTO/UserDTO.php

namespace App\DTO;

class UserDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly ?string $email = null,
    ) {}
}
```

### 2. Generate TypeScript

```bash
php vendor/bin/php-to-ts src/DTO
```

### 3. Check the Output

```typescript
// types/UserDTO.ts
export interface UserDTO {
  name: string;
  age: number;
  email: string | null;
}
```

## Advanced Usage

### Generate with Dependencies

```bash
php vendor/bin/php-to-ts src/DTO --with-dependencies
```

This will automatically generate all nested DTOs and enums referenced by your classes.

### Custom Output Directory

```bash
php vendor/bin/php-to-ts src/DTO --output=frontend/types
```

### Generate from Single File

```bash
php vendor/bin/php-to-ts src/DTO/UserDTO.php
```

## Programmatic Usage

```php
use PhpToTs\PhpToTsGenerator;

$generator = new PhpToTsGenerator();

// Single class
$typescript = $generator->generate(UserDTO::class);
file_put_contents('types/UserDTO.ts', $typescript);

// With dependencies
$files = $generator->generateWithDependencies(UserDTO::class);
foreach ($files as $className => $typescript) {
    file_put_contents("types/{$className}.ts", $typescript);
}
```

## Supported Features

✅ Primitive types (string, int, float, bool)
✅ Nullable types (`?Type`)
✅ Arrays and typed arrays (`string[]`, `CustomDTO[]`)
✅ Nested DTOs with imports
✅ PHP 8.1+ Enums
✅ DateTime objects
✅ Readonly properties
✅ JSDoc comments

## Next Steps

- Read the [full documentation](README.md)
- Check out [examples](examples/)
- Run tests: `composer test`
- Explore the [codebase architecture](README.md#architecture)

## Troubleshooting

### Classes not found?
Make sure your classes are autoloaded by Composer. Run:
```bash
composer dump-autoload
```

### Wrong types generated?
Check your PHPDoc comments. For arrays, use:
```php
/** @var string[] */
public readonly array $tags;
```

### Need help?
Check the [README](README.md) or open an issue on GitHub.
