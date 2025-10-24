# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
