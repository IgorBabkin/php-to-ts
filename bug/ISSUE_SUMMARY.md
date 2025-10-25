# Issue Summary for Library Contributors

## 🐛 Critical Bugs in php-to-ts-generator v1.3.0

### Issue #1: `generateWithDependencies()` Not Working
**Status**: ❌ BROKEN  
**Impact**: HIGH - Core functionality not working

**Problem**: The `generateWithDependencies()` method only generates the main class, ignoring all nested dependencies.

**Evidence**:
```php
$generator = new PhpToTsGenerator();
$files = $generator->generateWithDependencies(TariffEditMarginViewContextDTO::class);
// Returns: ['TariffEditMarginViewContextDTO' => '...']
// Missing: TariffMarginsItem, ProviderProfile, RoamingNetwork
```

**Expected**: Should generate all referenced classes automatically  
**Actual**: Only generates the main class

---

### Issue #2: PHPDoc Array Types in Constructor Parameters Not Parsed
**Status**: ❌ BROKEN  
**Impact**: MEDIUM - Type safety compromised

**Problem**: PHPDoc array type annotations in constructor parameters are ignored, resulting in `any[]` instead of proper typed arrays.

**Evidence**:
```php
/**
 * @param array<ProviderProfile> $providersList
 * @param array<string, string> $allPaymentOptions
 */
public function __construct(
    public readonly array $providersList,
    public readonly array $allPaymentOptions,
) {}
```

**Expected TypeScript**:
```typescript
providersList: ProviderProfile[];
allPaymentOptions: Record<string, string>;
```

**Actual TypeScript**:
```typescript
providersList: any[];
allPaymentOptions: any[];
```

---

### Issue #3: Inconsistent PHPDoc Parsing
**Status**: ⚠️ INCONSISTENT  
**Impact**: LOW - Confusing behavior

**Problem**: PHPDoc parsing works for regular properties but not for constructor parameters.

**Working Example** (Regular Property):
```php
/**
 * @var array<int, int>|null
 */
public ?array $paymentTypes = null;
```
→ `paymentTypes: Record<number, number>;` ✅

**Broken Example** (Constructor Parameter):
```php
/**
 * @param array<ProviderProfile> $providersList
 */
public function __construct(
    public readonly array $providersList,
) {}
```
→ `providersList: any[];` ❌

---

## 🔧 Suggested Fixes

### Fix #1: Nested Dependencies
The `generateWithDependencies()` method should:
1. Analyze all type references in the main class
2. Recursively analyze type references in nested classes
3. Generate separate TypeScript files for each unique class
4. Handle circular dependencies gracefully

### Fix #2: Constructor PHPDoc Parsing
The PHPDoc parser should:
1. Parse `@param` annotations in constructor docblocks
2. Map array types: `array<Type>` → `Type[]`
3. Map key-value arrays: `array<Key, Value>` → `Record<Key, Value>`
4. Handle nullable arrays: `array<Type>|null` → `Type[] | null`

### Fix #3: Consistent Parsing
Ensure PHPDoc parsing works identically for:
- Regular properties
- Constructor parameters
- Method parameters

---

## 📋 Test Cases

### Test Case 1: Nested Dependencies
```php
class UserDTO {
    public function __construct(
        public readonly string $name,
        public readonly AddressDTO $address,
        public readonly RoleEnum $role,
    ) {}
}
```

**Expected**: Generate `UserDTO.ts`, `AddressDTO.ts`, `RoleEnum.ts`  
**Actual**: Only generates `UserDTO.ts`

### Test Case 2: Array Types
```php
class CollectionDTO {
    /**
     * @param array<UserDTO> $users
     * @param array<string, int> $scores
     * @param array<string>|null $tags
     */
    public function __construct(
        public readonly array $users,
        public readonly array $scores,
        public readonly ?array $tags,
    ) {}
}
```

**Expected**:
```typescript
users: UserDTO[];
scores: Record<string, number>;
tags: string[] | null;
```

**Actual**:
```typescript
users: any[];
scores: any[];
tags: any[] | null;
```

---

## 🚀 Priority

1. **HIGH**: Fix `generateWithDependencies()` - Core functionality
2. **MEDIUM**: Fix constructor PHPDoc parsing - Type safety
3. **LOW**: Ensure consistent parsing behavior

---

## 📝 Reproduction

Run the included `REPRODUCTION_TEST.php` to see all issues in action.

**Files to check**:
- `BUG_REPORT.md` - Detailed technical report
- `REPRODUCTION_TEST.php` - Executable demonstration
- `.generated/` - Current broken output
