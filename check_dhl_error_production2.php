<?php

/**
 * Check Latest DHL Error Log - Production Version
 * 
 * Run via browser or SSH:
 * php check_dhl_error_production.php
 * 
 * Or via artisan tinker:
 * php artisan tinker --execute="require 'check_dhl_error_production.php';"
 */

$logPath = __DIR__ . '/storage/logs';

echo "=== Checking DHL Error Logs ===\n\n";

// Check for error JSON files
$errorFiles = glob($logPath . '/dhl-error-*.json');

if (empty($errorFiles)) {
    echo "❌ No DHL error log files found.\n";
    echo "   Path: {$logPath}\n\n";
    
    // Check Laravel log instead
    $laravelLog = $logPath . '/laravel.log';
    if (file_exists($laravelLog)) {
        echo "📋 Checking Laravel log for DHL errors...\n\n";
        
        $logContent = file_get_contents($laravelLog);
        
        // Search for DHL error entries
        if (preg_match_all('/DHL API Error - FULL DETAILS.*?\{.*?\}/s', $logContent, $matches)) {
            echo "Found " . count($matches[0]) . " DHL error entries in Laravel log:\n\n";
            foreach ($matches[0] as $index => $match) {
                echo "=== Error Entry #" . ($index + 1) . " ===\n";
                // Try to extract JSON from the log entry
                if (preg_match('/\{.*\}/s', $match, $jsonMatch)) {
                    $json = json_decode($jsonMatch[0], true);
                    if ($json) {
                        echo "Status: " . ($json['status'] ?? 'N/A') . "\n";
                        echo "Full Body Length: " . (isset($json['full_body_string']) ? strlen($json['full_body_string']) : 'N/A') . "\n";
                        echo "Has Additional Details: " . (isset($json['has_additional_details']) ? ($json['has_additional_details'] ? 'Yes' : 'No') : 'N/A') . "\n";
                        echo "Validation Errors Count: " . (isset($json['validation_errors_count']) ? $json['validation_errors_count'] : 'N/A') . "\n";
                        
                        if (isset($json['additional_details_sample'])) {
                            echo "\nSample Additional Detail:\n";
                            print_r($json['additional_details_sample']);
                        }
                        
                        if (isset($json['full_body_string'])) {
                            echo "\nFull Body String (first 2000 chars):\n";
                            echo substr($json['full_body_string'], 0, 2000) . "\n";
                            
                            // Try to parse it
                            $parsed = json_decode($json['full_body_string'], true);
                            if ($parsed && isset($parsed['additionalDetails'])) {
                                echo "\n✅ Found additionalDetails in parsed body!\n";
                                echo "Count: " . count($parsed['additionalDetails']) . "\n\n";
                                foreach ($parsed['additionalDetails'] as $i => $detail) {
                                    echo "Error #" . ($i + 1) . ":\n";
                                    echo "  Field: " . ($detail['field'] ?? 'N/A') . "\n";
                                    echo "  Message: " . ($detail['message'] ?? $detail['invalidValue'] ?? 'N/A') . "\n";
                                    if (isset($detail['value'])) {
                                        echo "  Value: " . $detail['value'] . "\n";
                                    }
                                    echo "\n";
                                }
                            }
                        }
                    }
                }
                echo "\n";
            }
        } else {
            echo "⚠️  No DHL error entries found in Laravel log.\n";
        }
    }
    
    exit(1);
}

// Get the most recent error file
usort($errorFiles, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

$latestFile = $errorFiles[0];
echo "✓ Found latest error log: " . basename($latestFile) . "\n";
echo "   Full path: {$latestFile}\n";
echo "   Modified: " . date('Y-m-d H:i:s', filemtime($latestFile)) . "\n\n";

$errorData = json_decode(file_get_contents($latestFile), true);

if (!$errorData) {
    echo "❌ Could not parse error log file.\n";
    exit(1);
}

echo "=== Error Summary ===\n";
echo "Status Code: " . ($errorData['status'] ?? 'N/A') . "\n";
echo "Endpoint: " . ($errorData['endpoint'] ?? 'N/A') . "\n";
echo "Method: " . ($errorData['method'] ?? 'N/A') . "\n\n";

if (isset($errorData['response_json'])) {
    $response = $errorData['response_json'];
    
    echo "=== DHL Error Response ===\n";
    echo "Title: " . ($response['title'] ?? 'N/A') . "\n";
    echo "Detail: " . ($response['detail'] ?? 'N/A') . "\n";
    echo "Instance: " . ($response['instance'] ?? 'N/A') . "\n\n";
    
    if (isset($response['additionalDetails']) && is_array($response['additionalDetails'])) {
        echo "=== Validation Errors ===\n";
        foreach ($response['additionalDetails'] as $index => $detail) {
            echo "\nError #" . ($index + 1) . ":\n";
            echo "  Field: " . ($detail['field'] ?? 'N/A') . "\n";
            echo "  Message: " . ($detail['message'] ?? 'N/A') . "\n";
            if (isset($detail['value'])) {
                echo "  Value: " . $detail['value'] . "\n";
            }
            if (isset($detail['invalidValue'])) {
                echo "  Invalid Value: " . $detail['invalidValue'] . "\n";
            }
        }
    } else {
        echo "⚠️  No validation errors found in additionalDetails\n";
        echo "   Response keys: " . implode(', ', array_keys($response)) . "\n";
        
        // Try parsing raw body
        if (isset($errorData['response_body_raw'])) {
            echo "\n📋 Trying to parse raw response body...\n";
            $parsed = json_decode($errorData['response_body_raw'], true);
            if ($parsed && isset($parsed['additionalDetails'])) {
                echo "✅ Found additionalDetails in raw body!\n";
                foreach ($parsed['additionalDetails'] as $index => $detail) {
                    echo "\nError #" . ($index + 1) . ":\n";
                    echo "  Field: " . ($detail['field'] ?? 'N/A') . "\n";
                    echo "  Message: " . ($detail['message'] ?? $detail['invalidValue'] ?? 'N/A') . "\n";
                }
            } else {
                echo "❌ Could not parse raw body or no additionalDetails found.\n";
                echo "   Raw body (first 500 chars): " . substr($errorData['response_body_raw'], 0, 500) . "\n";
            }
        }
    }
}

if (isset($errorData['validation_errors']) && !empty($errorData['validation_errors'])) {
    echo "\n=== Extracted Validation Errors ===\n";
    foreach ($errorData['validation_errors'] as $index => $error) {
        echo "\nError #" . ($index + 1) . ":\n";
        if (is_array($error)) {
            echo "  Field: " . ($error['field'] ?? 'N/A') . "\n";
            echo "  Message: " . ($error['message'] ?? 'N/A') . "\n";
        } else {
            echo "  Error: " . $error . "\n";
        }
    }
}

echo "\n\n=== Full Error Log (JSON) ===\n";
echo json_encode($errorData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
