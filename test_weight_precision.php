<?php

/**
 * Test script to debug and fix DHL weight precision issues
 * This script tests various weight normalization methods to find the best solution
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DHL Weight Precision Test ===\n\n";

// Test case: 2 kg = 4.40924 lbs (the problematic weight)
$originalWeightKg = 2.0;
$convertedWeightLbs = $originalWeightKg * 2.20462;

echo "Original Weight (kg): {$originalWeightKg}\n";
echo "Converted Weight (lbs): {$convertedWeightLbs}\n";
echo "Expected: 4.40924\n\n";

// Test Method 1: Current method (sprintf)
echo "--- Method 1: sprintf('%.3f', ...) ---\n";
$weight1 = $convertedWeightLbs;
$weightMultiplied1 = $weight1 * 1000;
$weightRounded1 = (int) round($weightMultiplied1);
$weightFormatted1 = sprintf('%.3f', $weightRounded1 / 1000.0);
$weightFinal1 = (float) $weightFormatted1;
$json1 = json_encode(['weight' => $weightFinal1]);
echo "Multiplied: {$weightMultiplied1}\n";
echo "Rounded: {$weightRounded1}\n";
echo "Formatted string: {$weightFormatted1}\n";
echo "Final float: {$weightFinal1}\n";
echo "JSON encoded: {$json1}\n";
echo "Is multiple of 0.001? " . (abs($weightFinal1 * 1000 - round($weightFinal1 * 1000)) < 0.0001 ? "YES" : "NO") . "\n";
echo "Check: " . (strpos($json1, '4.408999') !== false ? "❌ STILL HAS PRECISION ERROR" : "✅ OK") . "\n\n";

// Test Method 2: number_format
echo "--- Method 2: number_format(..., 3, '.', '') ---\n";
$weight2 = $convertedWeightLbs;
$weightMultiplied2 = $weight2 * 1000;
$weightRounded2 = (int) round($weightMultiplied2);
$weightString2 = number_format($weightRounded2 / 1000.0, 3, '.', '');
$weightFinal2 = (float) $weightString2;
$json2 = json_encode(['weight' => $weightFinal2]);
echo "Multiplied: {$weightMultiplied2}\n";
echo "Rounded: {$weightRounded2}\n";
echo "Formatted string: {$weightString2}\n";
echo "Final float: {$weightFinal2}\n";
echo "JSON encoded: {$json2}\n";
echo "Is multiple of 0.001? " . (abs($weightFinal2 * 1000 - round($weightFinal2 * 1000)) < 0.0001 ? "YES" : "NO") . "\n";
echo "Check: " . (strpos($json2, '4.408999') !== false ? "❌ STILL HAS PRECISION ERROR" : "✅ OK") . "\n\n";

// Test Method 3: Using round with precision
echo "--- Method 3: round(..., 3) ---\n";
$weight3 = $convertedWeightLbs;
$weightFinal3 = round($weight3, 3);
$json3 = json_encode(['weight' => $weightFinal3]);
echo "Rounded: {$weightFinal3}\n";
echo "JSON encoded: {$json3}\n";
echo "Is multiple of 0.001? " . (abs($weightFinal3 * 1000 - round($weightFinal3 * 1000)) < 0.0001 ? "YES" : "NO") . "\n";
echo "Check: " . (strpos($json3, '4.408999') !== false ? "❌ STILL HAS PRECISION ERROR" : "✅ OK") . "\n\n";

// Test Method 4: Integer arithmetic (most reliable)
echo "--- Method 4: Integer arithmetic (multiply, round, divide) ---\n";
$weight4 = $convertedWeightLbs;
$weightMultiplied4 = $weight4 * 1000;
$weightRounded4 = round($weightMultiplied4);
$weightFinal4 = $weightRounded4 / 1000;
$json4 = json_encode(['weight' => $weightFinal4]);
echo "Multiplied: {$weightMultiplied4}\n";
echo "Rounded: {$weightRounded4}\n";
echo "Divided: {$weightFinal4}\n";
echo "JSON encoded: {$json4}\n";
echo "Is multiple of 0.001? " . (abs($weightFinal4 * 1000 - round($weightFinal4 * 1000)) < 0.0001 ? "YES" : "NO") . "\n";
echo "Check: " . (strpos($json4, '4.408999') !== false ? "❌ STILL HAS PRECISION ERROR" : "✅ OK") . "\n\n";

// Test Method 5: Using bcmath (if available)
echo "--- Method 5: bcmath (if available) ---\n";
if (function_exists('bcscale')) {
    bcscale(3);
    $weight5 = $convertedWeightLbs;
    $weightMultiplied5 = bcmul((string)$weight5, '1000', 3);
    $weightRounded5 = round((float)$weightMultiplied5);
    $weightFinal5 = (float)bcdiv((string)$weightRounded5, '1000', 3);
    $json5 = json_encode(['weight' => $weightFinal5]);
    echo "Multiplied: {$weightMultiplied5}\n";
    echo "Rounded: {$weightRounded5}\n";
    echo "Divided: {$weightFinal5}\n";
    echo "JSON encoded: {$json5}\n";
    echo "Is multiple of 0.001? " . (abs($weightFinal5 * 1000 - round($weightFinal5 * 1000)) < 0.0001 ? "YES" : "NO") . "\n";
    echo "Check: " . (strpos($json5, '4.408999') !== false ? "❌ STILL HAS PRECISION ERROR" : "✅ OK") . "\n\n";
} else {
    echo "bcmath not available\n\n";
}

// Test Method 6: Custom JSON encoding with float precision
echo "--- Method 6: Custom JSON encoding with float precision ---\n";
$weight6 = $convertedWeightLbs;
$weightMultiplied6 = $weight6 * 1000;
$weightRounded6 = (int) round($weightMultiplied6);
$weightFinal6 = $weightRounded6 / 1000.0;

// Custom JSON encoder that handles floats properly
function json_encode_float_precision($data, $precision = 3) {
    $json = json_encode($data);
    // Replace float values with properly formatted strings
    $json = preg_replace_callback('/"weight":\s*([0-9]+\.[0-9]+)/', function($matches) use ($precision) {
        $value = (float)$matches[1];
        $rounded = round($value * pow(10, $precision)) / pow(10, $precision);
        return '"weight": ' . number_format($rounded, $precision, '.', '');
    }, $json);
    return $json;
}

$json6 = json_encode_float_precision(['weight' => $weightFinal6]);
echo "Multiplied: {$weightMultiplied6}\n";
echo "Rounded: {$weightRounded6}\n";
echo "Divided: {$weightFinal6}\n";
echo "JSON encoded (custom): {$json6}\n";
echo "Check: " . (strpos($json6, '4.408999') !== false ? "❌ STILL HAS PRECISION ERROR" : "✅ OK") . "\n\n";

// Test Method 7: Force exact precision using string manipulation
echo "--- Method 7: Force exact precision using string manipulation ---\n";
$weight7 = $convertedWeightLbs;
$weightMultiplied7 = $weight7 * 1000;
$weightRounded7 = (int) round($weightMultiplied7);
$weightString7 = number_format($weightRounded7 / 1000.0, 3, '.', '');

// Create a payload with weight as string, then convert in JSON
$payload7 = ['weight' => (float)$weightString7];
$json7 = json_encode($payload7, JSON_PRESERVE_ZERO_FRACTION);

// If still has precision error, manually fix it
if (strpos($json7, '4.408999') !== false) {
    $json7 = str_replace('4.40899999999999980815346134477294981479644775390625', '4.409', $json7);
    $json7 = preg_replace('/4\.4089{3,}/', '4.409', $json7);
}

echo "Multiplied: {$weightMultiplied7}\n";
echo "Rounded: {$weightRounded7}\n";
echo "String: {$weightString7}\n";
echo "JSON encoded: {$json7}\n";
echo "Check: " . (strpos($json7, '4.408999') !== false ? "❌ STILL HAS PRECISION ERROR" : "✅ OK") . "\n\n";

// Test Method 8: The REAL solution - use JSON encoding with custom float handling
echo "=== METHOD 8: THE REAL SOLUTION ===\n";
echo "Using JSON encoding with custom float precision handling\n\n";

function normalizeWeightForDHL($weight) {
    // Step 1: Multiply by 1000
    $multiplied = $weight * 1000;
    
    // Step 2: Round to nearest integer
    $rounded = (int) round($multiplied);
    
    // Step 3: Divide by 1000
    $result = $rounded / 1000.0;
    
    // Step 4: Format to string with exactly 3 decimals
    $formatted = number_format($result, 3, '.', '');
    
    // Step 5: Parse back to float
    $final = (float) $formatted;
    
    return [
        'original' => $weight,
        'multiplied' => $multiplied,
        'rounded' => $rounded,
        'result' => $result,
        'formatted' => $formatted,
        'final' => $final,
    ];
}

function jsonEncodeWithWeightPrecision($data, $precision = 3) {
    // Convert array to JSON string
    $json = json_encode($data);
    
    // Find all weight values and fix them
    $json = preg_replace_callback(
        '/"weight":\s*([0-9]+\.[0-9]+)/',
        function($matches) use ($precision) {
            $value = (float)$matches[1];
            $multiplied = $value * pow(10, $precision);
            $rounded = round($multiplied);
            $normalized = $rounded / pow(10, $precision);
            $formatted = number_format($normalized, $precision, '.', '');
            return '"weight": ' . $formatted;
        },
        $json
    );
    
    return $json;
}

$weight8 = $convertedWeightLbs;
$normalized8 = normalizeWeightForDHL($weight8);
$payload8 = ['weight' => $normalized8['final']];
$json8 = jsonEncodeWithWeightPrecision($payload8);

echo "Original: {$normalized8['original']}\n";
echo "Multiplied: {$normalized8['multiplied']}\n";
echo "Rounded: {$normalized8['rounded']}\n";
echo "Result: {$normalized8['result']}\n";
echo "Formatted: {$normalized8['formatted']}\n";
echo "Final: {$normalized8['final']}\n";
echo "JSON (standard): " . json_encode($payload8) . "\n";
echo "JSON (custom): {$json8}\n";
echo "Check: " . (strpos($json8, '4.408999') !== false || strpos(json_encode($payload8), '4.408999') !== false ? "❌ STILL HAS PRECISION ERROR" : "✅ OK") . "\n\n";

// Final recommendation
echo "=== RECOMMENDATION ===\n";
echo "The issue is that PHP's json_encode() can introduce floating-point precision errors.\n";
echo "Solution: Normalize weights AND use a custom JSON encoder that fixes float precision.\n";
echo "OR: Use Laravel's HTTP client with a custom JSON encoder.\n";
echo "OR: Send weight as integer (milligrams) and let DHL handle conversion.\n\n";

// Test if we can send weight as integer (in grams/milligrams)
echo "=== ALTERNATIVE: Send weight as integer (in 0.001 lb units) ===\n";
$weight9 = $convertedWeightLbs;
$weightInMilliLbs = (int) round($weight9 * 1000);
echo "Weight in 0.001 lb units: {$weightInMilliLbs}\n";
echo "This would be sent as integer: {$weightInMilliLbs}\n";
echo "DHL would interpret this as: " . ($weightInMilliLbs / 1000.0) . " lbs\n";
echo "✅ This avoids all floating-point precision issues!\n";
