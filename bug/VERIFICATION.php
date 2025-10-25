<?php

/**
 * Verification that both reported bugs are fixed
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PhpToTs\PhpToTsGenerator;
use PhpToTs\Tests\Fixtures\ConstructorParamArrayDTO;
use PhpToTs\Tests\Fixtures\NestedDependencyDTO;

echo "=== Bug Fix Verification ===\n\n";

$generator = new PhpToTsGenerator();

// Bug #1: PHPDoc @param array types in constructor parameters
echo "BUG #1: PHPDoc Array Types in Constructor Parameters\n";
echo "-----------------------------------------------------\n";
$typescript = $generator->generate(ConstructorParamArrayDTO::class);
echo $typescript . "\n";

// Verify fixes
$checks = [
    'addresses: AddressDTO[]' => strpos($typescript, 'addresses: AddressDTO[]') !== false,
    'scores: Record<string, number>' => strpos($typescript, 'scores: Record<string, number>') !== false,
    'tags: string[] | null' => strpos($typescript, 'tags: string[] | null') !== false,
];

echo "Verification:\n";
foreach ($checks as $check => $passed) {
    echo ($passed ? '✅' : '❌') . " $check\n";
}
echo "\n";

// Bug #2: generateWithDependencies() not generating nested classes
echo "BUG #2: Nested Dependencies Generation\n";
echo "---------------------------------------\n";
$files = $generator->generateWithDependencies(NestedDependencyDTO::class);

echo "Generated files:\n";
foreach (array_keys($files) as $className) {
    echo "  - $className.ts\n";
}
echo "\n";

$expectedFiles = ['NestedDependencyDTO', 'AddressDTO', 'UserDTO', 'RoleEnum'];
$allPresent = true;
foreach ($expectedFiles as $expected) {
    $present = isset($files[$expected]);
    $allPresent = $allPresent && $present;
    echo ($present ? '✅' : '❌') . " $expected\n";
}

echo "\n";
echo ($allPresent && count($checks) === array_sum($checks) ? '✅ ALL BUGS FIXED!' : '❌ Some issues remain') . "\n";
