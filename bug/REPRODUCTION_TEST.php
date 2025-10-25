<?php

/**
 * Minimal reproduction test for php-to-ts-generator bugs
 * 
 * This script demonstrates the issues with:
 * 1. Missing nested dependency generation
 * 2. PHPDoc array type parsing in constructor parameters
 */

use PhpToTs\PhpToTsGenerator;

require_once __DIR__ . '/bin/autoload/autoload.php';

echo "=== PHP-to-TS Generator Bug Reproduction Test ===\n\n";

// Test 1: Missing nested dependencies
echo "Test 1: Nested Dependencies\n";
echo "Expected: Should generate UserDTO.ts, AddressDTO.ts, and RoleEnum.ts\n";
echo "Actual: Only generates UserDTO.ts\n\n";

$generator = new PhpToTsGenerator();

try {
    $files = $generator->generateWithDependencies(\LMS\EV\View\Tariff\TariffEditMarginViewContextDTO::class);
    
    echo "Generated files:\n";
    foreach ($files as $className => $typescript) {
        $parts = explode('\\', $className);
        $simpleClassName = end($parts);
        echo "- $simpleClassName.ts\n";
    }
    
    echo "\nExpected files (missing):\n";
    echo "- TariffMarginsItem.ts\n";
    echo "- ProviderProfile.ts\n";
    echo "- RoamingNetwork.ts\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test 2: PHPDoc array type parsing
echo "Test 2: PHPDoc Array Type Parsing\n";
echo "Expected: providersList should be ProviderProfile[], not any[]\n";
echo "Expected: allPaymentOptions should be Record<string, string>, not any[]\n\n";

try {
    $typescript = $generator->generate(\LMS\EV\View\Tariff\TariffEditMarginViewContextDTO::class);
    
    // Extract the problematic lines
    $lines = explode("\n", $typescript);
    foreach ($lines as $line) {
        if (strpos($line, 'providersList:') !== false || 
            strpos($line, 'roamingNetworks:') !== false ||
            strpos($line, 'allPaymentOptions:') !== false) {
            echo "Found: $line\n";
        }
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test 3: Working example (regular property PHPDoc)
echo "Test 3: Working Example (Regular Property PHPDoc)\n";
echo "This demonstrates that PHPDoc parsing works for regular properties\n\n";

try {
    $typescript = $generator->generate(\LMS\EV\Entity\TariffMarginsItem::class);
    
    // Extract the working example
    $lines = explode("\n", $typescript);
    foreach ($lines as $line) {
        if (strpos($line, 'paymentTypes:') !== false) {
            echo "Working example: $line\n";
            break;
        }
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
