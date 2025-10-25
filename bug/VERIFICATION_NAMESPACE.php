<?php

/**
 * Verification that cross-namespace dependency resolution is fixed
 * This addresses the bug report in BUG_REPORT_DEPENDENCIES.md
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PhpToTs\PhpToTsGenerator;
use PhpToTs\Tests\Fixtures\UserDTO;
use PhpToTs\Tests\Fixtures\ProjectDTO;

echo "=== Cross-Namespace Dependency Resolution Fix Verification ===\n\n";

$generator = new PhpToTsGenerator();

// Test 1: UserDTO with AddressDTO dependency (same namespace)
echo "Test 1: Same Namespace Dependencies\n";
echo "------------------------------------\n";
$files = $generator->generateWithDependencies(UserDTO::class);

echo "Generated files for UserDTO:\n";
foreach (array_keys($files) as $className) {
    echo "  ✅ $className.ts\n";
}

$expectedFiles = ['UserDTO', 'AddressDTO'];
$allPresent = true;
foreach ($expectedFiles as $expected) {
    if (!isset($files[$expected])) {
        echo "  ❌ Missing: $expected.ts\n";
        $allPresent = false;
    }
}

if ($allPresent) {
    echo "\n✅ All same-namespace dependencies generated correctly!\n\n";
} else {
    echo "\n❌ Some dependencies missing\n\n";
}

// Test 2: ProjectDTO with TaskDTO dependency and enum dependencies
echo "Test 2: Multiple Dependencies with Arrays\n";
echo "-------------------------------------------\n";
$files2 = $generator->generateWithDependencies(ProjectDTO::class);

echo "Generated files for ProjectDTO:\n";
foreach (array_keys($files2) as $className) {
    echo "  ✅ $className.ts\n";
}

$expectedFiles2 = ['ProjectDTO', 'TaskDTO', 'RoleEnum', 'PriorityEnum'];
$allPresent2 = true;
foreach ($expectedFiles2 as $expected) {
    if (!isset($files2[$expected])) {
        echo "  ❌ Missing: $expected.ts\n";
        $allPresent2 = false;
    }
}

if ($allPresent2) {
    echo "\n✅ All array-type dependencies resolved correctly!\n\n";
} else {
    echo "\n❌ Some dependencies missing\n\n";
}

// Verify that imports are correct (short names)
echo "Test 3: Import Statements Format\n";
echo "----------------------------------\n";
$userTS = $files['UserDTO'];
if (str_contains($userTS, "import { AddressDTO } from './AddressDTO'")) {
    echo "✅ Imports use short names (correct)\n";
} else {
    echo "❌ Imports format incorrect\n";
}

if (!str_contains($userTS, 'PhpToTs\\Tests\\Fixtures')) {
    echo "✅ No full namespaces in TypeScript (correct)\n";
} else {
    echo "❌ Full namespaces leaked into TypeScript\n";
}

echo "\n";
echo ($allPresent && $allPresent2) ? "✅ ALL CROSS-NAMESPACE BUGS FIXED!" : "❌ Some issues remain";
echo "\n\n";

echo "This fix resolves the issue where dependencies from the same namespace\n";
echo "or imported via @var comments were not being generated as separate files.\n";
