<?php

// Test the JSON fix function
$json = '{"weight":4.40899999999999980815346134477294981479644775390625}';
echo "Original JSON: {$json}\n\n";

$pattern = '/"weight"\s*:\s*([0-9]+\.[0-9]+)/';

$fixed = preg_replace_callback($pattern, function($matches) {
    $value = (float)$matches[1];
    
    // Normalize to exact multiple of 0.001
    $multiplied = $value * 1000;
    $rounded = (int) round($multiplied);
    $normalized = $rounded / 1000.0;
    
    // Format to exactly 3 decimal places
    $formatted = number_format($normalized, 3, '.', '');
    
    echo "Original value: {$matches[1]}\n";
    echo "Multiplied: {$multiplied}\n";
    echo "Rounded: {$rounded}\n";
    echo "Normalized: {$normalized}\n";
    echo "Formatted: {$formatted}\n\n";
    
    return '"weight": ' . $formatted;
}, $json);

echo "Fixed JSON: {$fixed}\n";
echo "Check: " . (strpos($fixed, '4.408999') !== false ? "❌ STILL HAS ERROR" : "✅ FIXED!") . "\n";
