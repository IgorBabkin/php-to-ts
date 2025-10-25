# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.5.0] - 2025-10-25

### Added
- **Namespace-Based Input (PSR-4)**: Generate TypeScript from namespace patterns instead of file paths
  - Use `--base-dir` to specify PSR-4 base directory (e.g., `src`)
  - Use `--namespace-prefix` to map namespace to directory (e.g., `App` maps to `src/`)
  - Supports single class: `vendor/bin/php-to-ts "App\DTO\UserDTO" --base-dir=src --namespace-prefix="App"`
  - Supports glob patterns: `vendor/bin/php-to-ts "App\DTO\*" --base-dir=src --namespace-prefix="App"`
  - Example: `"LMS\EV\View\Tariff\*"` generates all Tariff DTOs
- New `NamespaceResolver` class for PSR-4 namespace-to-file resolution
  - Converts namespace patterns to file paths following PSR-4 standard
  - Supports glob patterns: `\App\DTO\*` (direct children) or `\App\DTO\*\*` (recursive)
  - Handles namespace prefix removal for proper path mapping

### Changed
- CLI now supports both file-based and namespace-based input
  - File-based: `vendor/bin/php-to-ts src/DTO` (original behavior, still works)
  - Namespace-based: `vendor/bin/php-to-ts "App\DTO\*" --base-dir=src` (new)
- Refactored command execution to process classes directly (more efficient)
- Progress bar now shows class count instead of file count

### Benefits
- More intuitive for large projects with organized namespaces
- Generate DTOs by feature area: `"\LMS\View\Tariff\*"`, `"\App\Api\Transaction\*"`
- No need to know exact file paths, just namespace patterns
- Better integration with PSR-4 autoloading standards

### Examples
```bash
# Old way (still works)
vendor/bin/php-to-ts src/LMS/EV/View/Tariff -o types/

# New way (cleaner)
vendor/bin/php-to-ts "LMS\EV\View\Tariff\*" --base-dir=src --namespace-prefix="LMS\EV" -o types/

# Multiple namespaces
vendor/bin/php-to-ts "App\DTO\*" --base-dir=src --namespace-prefix="App" -o types/
vendor/bin/php-to-ts "App\Api\*" --base-dir=src --namespace-prefix="App" -o types/
```

## [1.4.1] - 2025-10-25

### Fixed
- **CRITICAL**: Cross-namespace dependency resolution now works correctly
  - Dependencies from the same namespace as parent class are now properly resolved
  - `generateWithDependencies()` now generates ALL nested class files, not just some
  - Fixed issue where classes referenced in `@var` arrays weren't being generated
  - Example: `@var TaskDTO[]` now correctly generates `TaskDTO.ts` even if in same namespace
- Dependencies now store full class names internally to enable cross-namespace resolution
- Import statements correctly use short names (no change to generated TypeScript)

### Changed
- `ClassAnalyzer::extractDependencies()` now returns full class names with namespaces
- Added multi-strategy resolution: same namespace → loaded classes → fallback
- `TypeScriptGenerator` now extracts short names from full class names for imports
- Removed `resolveDependencyClass()` method (no longer needed)

### Technical Details
- Uses ReflectionClass namespace context to resolve short class names
- Tries `class_exists()`, `interface_exists()`, `enum_exists()`, and `trait_exists()`
- Falls back to searching loaded classes if not in same namespace
- Dependencies list is now authoritative source of truth for class resolution

### Tests
- 81 tests passing (1 pre-existing known issue unchanged)
- Updated `ClassAnalyzerTest` to expect full class names in dependencies
- Verification script confirms cross-namespace resolution works
- All integration tests pass with new implementation

## [1.4.0] - 2025-10-25

### Added
- **--add-ts-extension-to-imports** CLI flag to add `.ts` extension to import paths
  - Generates `import { User } from './User.ts'` instead of `import { User } from './User'`
  - Useful for ESM-only environments that require explicit file extensions
  - Available in both CLI and programmatic usage: `new PhpToTsGenerator(addTsExtensionToImports: true)`

### Changed
- `PhpToTsGenerator` constructor now accepts optional `addTsExtensionToImports` parameter
- `TypeScriptGenerator` updated to pass flag through to templates
- Twig template `interface.twig` conditionally adds `.ts` based on flag

### Tests
- 81 tests passing (1 known issue with nested shaped arrays remains)
- 5 new tests for `.ts` extension functionality with snapshot coverage
- New test file: `TsExtensionImportTest`

## [1.3.1] - 2025-10-25

### Fixed
- **CRITICAL**: PHPDoc `@param` array types in constructor parameters are now properly parsed
  - `@param array<UserDTO> $users` now generates `users: UserDTO[]` instead of `users: any[]`
  - `@param array<string, int> $scores` now generates `scores: Record<string, number>` instead of `scores: any[]`
  - `@param array<string>|null $tags` now generates `tags: string[] | null` instead of `tags: any[] | null`
- **CRITICAL**: `generateWithDependencies()` now correctly generates all nested class dependencies
  - Previously only generated the main class, now generates all referenced classes
  - Dependencies from complex array types are now tracked and generated
  - Constructor parameter array types now trigger dependency generation
- Nullable modifier is now preserved when using complex array types

### Changed
- `ClassAnalyzer` now reads constructor docblock for `@param` tags
- `extractParamDocComment()` improved regex to handle spaces in complex types
- `extractComplexArrayType()` now looks for both `@var` and `@param` tags
- `extractArrayItemType()` now handles `array<Type>` syntax from `@param` tags
- `extractDependencies()` now extracts class names from complex array types
- `TwigExtension` applies nullable mapping to complex array types

### Added
- New method `extractParamDocComment()` to extract `@param` tags for properties
- New method `extractClassNamesFromComplexType()` to find dependencies in complex arrays
- 3 new integration tests with snapshot coverage
- Bug verification script to confirm fixes

### Tests
- 76 tests passing (1 known issue with nested shaped arrays remains)
- New fixtures: `ConstructorParamArrayDTO`, `NestedDependencyDTO`
- New test: `ConstructorParamArrayTest` with 3 test cases

## [1.3.0] - 2025-10-25

### Added
- `#[Exclude]` attribute to exclude properties from TypeScript generation
- Complex PHPDoc array type support:
  - `array{foo: int, bar: string}` → `{ foo: number; bar: string }` (shaped arrays)
  - `array<string, int>` → `Record<string, number>` (generic key-value arrays)
  - `array<string>` → `string[]` (generic single-type arrays)
  - Optional fields: `array{id: int, name?: string}` → `{ id: number; name?: string }`
  - Nullable fields: `array{email: string|null}` → `{ email: string | null }`
- ComplexArrayTypeParser for parsing complex array types
- 16 new tests (75 total tests, 233 assertions)
- New fixtures: ShapedArrayDTO, GenericArrayDTO, ExcludeAttributeDTO, ExcludeClassPropertyDTO

### Changed
- PropertyInfo now includes complexArrayType field
- ClassAnalyzer skips properties with #[Exclude] attribute
- TwigExtension checks for complex array types before regular mapping

### Known Issues
- Nested shaped arrays (e.g., `array{user: array{id: int}}`) partially supported (1 test failing)

## [1.2.0] - 2025-10-25

### Added
- Support for regular class properties (not just constructor properties)
- Int enum support: numeric values without quotes in TypeScript enums
- PHPDoc tag preservation: `@deprecated`, `@see`, `@link`, `@example`, `@var`
- 25 new tests for class properties, comprehensive generation, and PHPDoc support
- Comprehensive test fixtures: ProjectDTO, TaskDTO, RoleEnum, PriorityEnum

### Fixed
- Int enums now generate without quotes (e.g., `LOW = 1` instead of `LOW = '1'`)
- Methods are always omitted from TypeScript interfaces
- PHPDoc comments with useful tags are now preserved in generated TypeScript

### Changed
- Improved doc comment extraction to preserve TypeScript-relevant tags
- Enhanced enum generation template to handle string vs int backing types

## [1.1.0] - 2025-10-25

### Changed
- **BREAKING**: Nested class dependencies are now generated by default (previously required `--with-dependencies` flag)
- CLI now automatically creates separate TypeScript files for all nested classes
- Generated files will overwrite existing TypeScript files

### Added
- `--no-dependencies` flag to disable automatic nested class generation
- Duplicate class tracking to avoid generating the same file multiple times within a single run
- 11 new tests for nested generation and duplicate tracking behavior

### Removed
- `--with-dependencies` flag (now default behavior, use `--no-dependencies` to disable)

### Fixed
- Nested classes are now always generated in separate files
- Duplicate classes are tracked to prevent generating the same file twice in a single run

## [1.0.0] - 2025-10-25

### Added
- Initial release of PHP to TypeScript Generator
- Support for converting PHP DTO classes to TypeScript interfaces
- Support for nested classes with automatic dependency resolution
- Support for PHP 8.1+ enums conversion to TypeScript enums
- Support for typed arrays (e.g., `string[]`, `CustomDTO[]`)
- Support for nullable types (e.g., `Type | null`)
- Support for readonly properties
- Support for DateTime conversion to string
- JSDoc comment preservation from PHP docblocks
- CLI command for batch generation
- `--with-dependencies` flag to generate all dependent types
- Comprehensive test suite with PHPUnit
- Snapshot testing for generated TypeScript
- Full documentation and examples

### Features
- **ClassAnalyzer**: Reflection-based PHP class analysis
- **TypeMapper**: Intelligent PHP to TypeScript type mapping
- **TypeScriptGenerator**: Twig-based TypeScript code generation
- **CLI Tool**: Symfony Console-based command-line interface

### Type Mappings
- `string` → `string`
- `int`, `float` → `number`
- `bool` → `boolean`
- `array` → `any[]` or typed arrays
- `DateTime*` → `string`
- `mixed` → `any`
- Custom classes → TypeScript interfaces with imports

### Requirements
- PHP 8.1 or higher
- Composer for dependency management
