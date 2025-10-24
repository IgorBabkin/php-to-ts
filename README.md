# PHP to TypeScript Generator

Generate TypeScript interfaces from PHP DTO classes with full support for nested classes, enums, arrays, and complex types.

## Features

- ✅ **Simple DTOs**: Convert PHP classes with primitive types to TypeScript interfaces
- ✅ **Nested Classes**: Automatically handles nested DTOs and generates proper imports
- ✅ **Automatic Dependency Generation**: By default, generates all nested class files separately (disable with `--no-dependencies`)
- ✅ **Deep Nesting**: Supports deeply nested structures (3+ levels)
- ✅ **Arrays & Collections**: Typed arrays with proper TypeScript syntax
- ✅ **Enums**: PHP 8.1+ enums converted to TypeScript enums
- ✅ **Nullable Types**: Proper handling of nullable properties
- ✅ **DateTime**: Converts DateTime objects to string or Date
- ✅ **Readonly Properties**: Respects PHP 8.1+ readonly modifier
- ✅ **JSDoc Comments**: Preserves documentation from PHP docblocks
- ✅ **Duplicate Prevention**: Tracks classes within a single run to avoid generating duplicates

## Requirements

- PHP 8.1 or higher
- Composer

## Installation

```bash
composer require php-to-ts-generator/php-to-ts-generator
```

## Usage

### CLI Command

**Basic usage** (automatically generates all nested class files):

```bash
vendor/bin/php-to-ts src/DTO --output=types/
```

**Generate from a single file** (with all nested dependencies):

```bash
vendor/bin/php-to-ts src/DTO/UserDTO.php -o types/
```

**Generate without nested dependencies**:

```bash
vendor/bin/php-to-ts src/DTO --no-dependencies
```

**Custom output directory**:

```bash
vendor/bin/php-to-ts src/DTO -o frontend/types
```

### Programmatic Usage

```php
use PhpToTs\PhpToTsGenerator;

$generator = new PhpToTsGenerator();

// Generate single class
$typescript = $generator->generate(UserDTO::class);
file_put_contents('types/UserDTO.ts', $typescript);

// Generate with all dependencies
$files = $generator->generateWithDependencies(UserDTO::class);
foreach ($files as $className => $typescript) {
    file_put_contents("types/{$className}.ts", $typescript);
}
```

## Examples

### Simple DTO

**PHP:**
```php
namespace App\DTO;

/**
 * User data transfer object
 */
class UserDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly bool $isActive,
        public readonly ?string $email = null,
    ) {}
}
```

**Generated TypeScript:**
```typescript
/**
 * User data transfer object
 */
export interface UserDTO {
  name: string;
  age: number;
  isActive: boolean;
  email: string | null;
}
```

### Nested DTOs

**PHP:**
```php
namespace App\DTO;

class AddressDTO
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
    ) {}
}

class UserDTO
{
    public function __construct(
        public readonly string $name,
        public readonly AddressDTO $address,
    ) {}
}
```

**Generated TypeScript:**
```typescript
// AddressDTO.ts
export interface AddressDTO {
  street: string;
  city: string;
}

// UserDTO.ts
import { AddressDTO } from './AddressDTO';

export interface UserDTO {
  name: string;
  address: AddressDTO;
}
```

### Arrays & Collections

**PHP:**
```php
namespace App\DTO;

class CollectionDTO
{
    public function __construct(
        /** @var string[] */
        public readonly array $tags,
        /** @var AddressDTO[] */
        public readonly array $addresses,
    ) {}
}
```

**Generated TypeScript:**
```typescript
import { AddressDTO } from './AddressDTO';

export interface CollectionDTO {
  tags: string[];
  addresses: AddressDTO[];
}
```

### Enums

**PHP:**
```php
namespace App\DTO;

/**
 * User status enum
 */
enum UserStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
}

class UserDTO
{
    public function __construct(
        public readonly string $name,
        public readonly UserStatus $status,
    ) {}
}
```

**Generated TypeScript:**
```typescript
// UserStatus.ts
/**
 * User status enum
 */
export enum UserStatus {
  ACTIVE = 'active',
  INACTIVE = 'inactive',
  SUSPENDED = 'suspended',
}

// UserDTO.ts
import { UserStatus } from './UserStatus';

export interface UserDTO {
  name: string;
  status: UserStatus;
}
```

## Type Mapping

| PHP Type | TypeScript Type |
|----------|----------------|
| `string` | `string` |
| `int`, `float` | `number` |
| `bool` | `boolean` |
| `array` | `any[]` |
| `string[]` (docblock) | `string[]` |
| `CustomClass` | `CustomClass` |
| `?Type` | `Type \| null` |
| `DateTime` | `string` |
| `mixed` | `any` |

## Development

### Install Dependencies

```bash
composer install
```

### Run Tests

```bash
composer test
```

### Run Tests with Coverage

```bash
composer test-coverage
```

### Test Structure

- **Unit Tests**: Test individual components (TypeMapper, ClassAnalyzer)
- **Integration Tests**: Test full generation pipeline with snapshot testing
- **Fixtures**: Sample DTOs for testing various scenarios

## Architecture

```
PhpToTsGenerator (Main Entry)
    ↓
ClassAnalyzer (Reflection-based analysis)
    ↓
ClassInfo + PropertyInfo (Data structures)
    ↓
TypeScriptGenerator (Uses Twig templates)
    ↓
TypeScript Output
```

### Core Components

- **ClassAnalyzer**: Uses PHP Reflection to analyze class structure
- **PropertyAnalyzer**: Extracts property information including types and docblocks
- **TypeMapper**: Maps PHP types to TypeScript equivalents
- **TypeScriptGenerator**: Generates TypeScript code using Twig templates
- **GenerateCommand**: Symfony Console command for CLI usage

## Contributing

Contributions are welcome! Please ensure:

1. All tests pass
2. New features have corresponding tests
3. Code follows PSR-12 coding standards

## License

MIT License

## Author

PHP to TS Generator Contributors
