# Testing Plan: Verify Published Package

This document outlines the steps to verify that the `igorbabkin/php-to-ts-generator` package works correctly after publishing to Packagist.

## Prerequisites

- ✅ Package submitted to Packagist
- ✅ Tag v1.0.0 exists on GitHub
- ⏳ Packagist has indexed the package (wait 5-10 minutes after submission)

---

## Phase 1: Fresh Installation Test

### 1.1 Create Test Project

```bash
# Navigate to a temp directory
cd /tmp

# Create a fresh test project
mkdir php-to-ts-test
cd php-to-ts-test

# Initialize composer
composer init --no-interaction \
  --name="test/php-to-ts-test" \
  --require="php:>=8.1"
```

### 1.2 Install Package from Packagist

```bash
# Install your package
composer require igorbabkin/php-to-ts-generator

# Verify installation
composer show igorbabkin/php-to-ts-generator
```

**Expected Output:**
- Package version: `1.0.0`
- Dependencies installed: symfony/console, twig/twig, nikic/php-parser
- Binary available: `vendor/bin/php-to-ts`

---

## Phase 2: CLI Testing

### 2.1 Create Test DTOs

```bash
mkdir -p src/DTO
```

**Create `src/DTO/UserDTO.php`:**
```php
<?php

namespace Test\DTO;

class UserDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly ?string $email = null,
    ) {}
}
```

**Create `src/DTO/AddressDTO.php`:**
```php
<?php

namespace Test\DTO;

class AddressDTO
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
    ) {}
}
```

**Create `src/DTO/CompanyDTO.php`:**
```php
<?php

namespace Test\DTO;

class CompanyDTO
{
    public function __construct(
        public readonly string $name,
        public readonly AddressDTO $address,
        /** @var UserDTO[] */
        public readonly array $employees,
    ) {}
}
```

### 2.2 Update composer.json for Autoloading

```bash
# Add autoload to composer.json
cat > composer.json << 'EOF'
{
    "name": "test/php-to-ts-test",
    "require": {
        "php": ">=8.1",
        "igorbabkin/php-to-ts-generator": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "Test\\": "src/"
        }
    }
}
EOF

# Regenerate autoload
composer dump-autoload
```

### 2.3 Test CLI Command

```bash
# Basic generation
php vendor/bin/php-to-ts src/DTO

# Check output directory
ls -la types/

# Verify content
cat types/UserDTO.ts
cat types/AddressDTO.ts
cat types/CompanyDTO.ts
```

**Expected Results:**
- ✅ 3 TypeScript files generated
- ✅ UserDTO.ts contains proper interface
- ✅ CompanyDTO.ts has imports for AddressDTO and UserDTO
- ✅ Arrays are properly typed

### 2.4 Test with Dependencies Flag

```bash
# Clean output
rm -rf types/

# Generate with dependencies
php vendor/bin/php-to-ts src/DTO/CompanyDTO.php --with-dependencies

# Verify all dependencies are generated
ls -la types/
```

**Expected Results:**
- ✅ CompanyDTO.ts generated
- ✅ AddressDTO.ts generated (dependency)
- ✅ UserDTO.ts generated (dependency)

---

## Phase 3: Programmatic API Testing

### 3.1 Create Test Script

**Create `test.php`:**
```php
<?php

require 'vendor/autoload.php';

use PhpToTs\PhpToTsGenerator;
use Test\DTO\CompanyDTO;
use Test\DTO\UserDTO;

$generator = new PhpToTsGenerator();

echo "=== Test 1: Generate Single Class ===\n";
$typescript = $generator->generate(UserDTO::class);
echo $typescript;
echo "\n";

echo "=== Test 2: Generate with Dependencies ===\n";
$files = $generator->generateWithDependencies(CompanyDTO::class);
echo "Generated " . count($files) . " files:\n";
foreach ($files as $name => $content) {
    echo "  - {$name}.ts\n";
}
echo "\n";

echo "=== Test 3: Verify Imports ===\n";
if (str_contains($files['CompanyDTO'], "import { AddressDTO }")) {
    echo "✅ AddressDTO import found\n";
} else {
    echo "❌ AddressDTO import missing\n";
}

if (str_contains($files['CompanyDTO'], "import { UserDTO }")) {
    echo "✅ UserDTO import found\n";
} else {
    echo "❌ UserDTO import missing\n";
}

echo "\n=== All Tests Passed! ===\n";
```

### 3.2 Run Test Script

```bash
php test.php
```

**Expected Output:**
```
=== Test 1: Generate Single Class ===
export interface UserDTO {
  name: string;
  age: number;
  email: string | null;
}

=== Test 2: Generate with Dependencies ===
Generated 3 files:
  - CompanyDTO.ts
  - AddressDTO.ts
  - UserDTO.ts

=== Test 3: Verify Imports ===
✅ AddressDTO import found
✅ UserDTO import found

=== All Tests Passed! ===
```

---

## Phase 4: Enum Testing

### 4.1 Create Enum DTO

**Create `src/DTO/UserStatus.php`:**
```php
<?php

namespace Test\DTO;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case BANNED = 'banned';
}
```

**Create `src/DTO/UserWithStatusDTO.php`:**
```php
<?php

namespace Test\DTO;

class UserWithStatusDTO
{
    public function __construct(
        public readonly string $name,
        public readonly UserStatus $status,
    ) {}
}
```

### 4.2 Test Enum Generation

```bash
# Regenerate
composer dump-autoload
rm -rf types/
php vendor/bin/php-to-ts src/DTO --with-dependencies

# Check enum output
cat types/UserStatus.ts
cat types/UserWithStatusDTO.ts
```

**Expected Results:**
- ✅ UserStatus.ts is an enum (not interface)
- ✅ Contains all cases (ACTIVE, INACTIVE, BANNED)
- ✅ UserWithStatusDTO.ts imports UserStatus

---

## Phase 5: TypeScript Validation

### 5.1 Install TypeScript (Optional)

```bash
npm init -y
npm install --save-dev typescript
npx tsc --init
```

### 5.2 Validate Generated TypeScript

```bash
# Compile TypeScript to check for errors
npx tsc types/*.ts --noEmit --strict
```

**Expected Results:**
- ✅ No TypeScript compilation errors
- ✅ All types are valid
- ✅ Imports resolve correctly

---

## Phase 6: Edge Cases Testing

### 6.1 Test Nullable Arrays

**Create `src/DTO/OptionalDTO.php`:**
```php
<?php

namespace Test\DTO;

class OptionalDTO
{
    public function __construct(
        /** @var string[]|null */
        public readonly ?array $tags = null,
    ) {}
}
```

### 6.2 Test DateTime

**Create `src/DTO/TimestampDTO.php`:**
```php
<?php

namespace Test\DTO;

class TimestampDTO
{
    public function __construct(
        public readonly \DateTimeImmutable $createdAt,
        public readonly ?\DateTimeImmutable $updatedAt = null,
    ) {}
}
```

### 6.3 Generate and Verify

```bash
composer dump-autoload
php vendor/bin/php-to-ts src/DTO
cat types/OptionalDTO.ts
cat types/TimestampDTO.ts
```

**Expected Results:**
- ✅ Nullable arrays: `tags: string[] | null`
- ✅ DateTime: `createdAt: string`
- ✅ Nullable DateTime: `updatedAt: string | null`

---

## Phase 7: Performance Test

### 7.1 Create Multiple DTOs

```bash
# Create 50 test DTOs
for i in {1..50}; do
  cat > "src/DTO/TestDTO${i}.php" << EOF
<?php
namespace Test\DTO;
class TestDTO${i} {
    public function __construct(
        public readonly string \$field1,
        public readonly int \$field2,
    ) {}
}
EOF
done
```

### 7.2 Test Batch Generation

```bash
# Time the generation
time php vendor/bin/php-to-ts src/DTO

# Count files
ls types/*.ts | wc -l
```

**Expected Results:**
- ✅ All 50+ files generated
- ✅ Completes in reasonable time (< 5 seconds)
- ✅ No errors or warnings

---

## Phase 8: Packagist Verification

### 8.1 Check Packagist Page

Visit: `https://packagist.org/packages/igorbabkin/php-to-ts-generator`

**Verify:**
- ✅ Package appears with correct name
- ✅ Description is accurate
- ✅ Latest version shows v1.0.0
- ✅ GitHub link works
- ✅ License shows MIT
- ✅ Dependencies listed correctly

### 8.2 Verify Auto-Update Hook

Check if GitHub webhook is configured:
1. Go to: `https://github.com/IgorBabkin/php-to-ts/settings/hooks`
2. Look for Packagist webhook
3. If missing, add it manually from Packagist package page

---

## Checklist: Success Criteria

- [ ] Package installs via `composer require`
- [ ] CLI command works: `php vendor/bin/php-to-ts`
- [ ] Simple DTOs generate correctly
- [ ] Nested DTOs include imports
- [ ] Arrays with types work (`string[]`, `DTO[]`)
- [ ] Nullable types generate `Type | null`
- [ ] Enums generate as TypeScript enums
- [ ] DateTime converts to `string`
- [ ] Programmatic API works
- [ ] Generated TypeScript is valid (compiles with tsc)
- [ ] Documentation is accessible
- [ ] Packagist page looks correct
- [ ] Auto-update webhook configured

---

## Troubleshooting

### Package Not Found
- Wait 5-10 minutes after Packagist submission
- Run: `composer clear-cache`
- Check Packagist page for indexing status

### Autoload Issues
- Run: `composer dump-autoload`
- Verify namespace matches directory structure

### Generation Errors
- Check PHP version: `php -v` (must be >= 8.1)
- Verify classes are loaded: `composer dump-autoload`
- Check file permissions on output directory

---

## Cleanup

```bash
# When testing is complete
cd ..
rm -rf php-to-ts-test
```

---

## Final Report Template

**Package Testing Report**

- Package Name: `igorbabkin/php-to-ts-generator`
- Version Tested: `1.0.0`
- Test Date: `YYYY-MM-DD`
- PHP Version: `X.X.X`
- Composer Version: `X.X.X`

**Results:**
- ✅/❌ Installation
- ✅/❌ CLI Tool
- ✅/❌ Programmatic API
- ✅/❌ Nested DTOs
- ✅/❌ Enums
- ✅/❌ TypeScript Validation

**Issues Found:** None / [List issues]

**Conclusion:** Package is production-ready ✅
