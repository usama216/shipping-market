<?php
/**
 * Remove Email Verification Backdoor Fix
 * 
 * This script removes the development backdoor that allowed email verification
 * with the "00000" code. Run this on cPanel after uploading the updated files.
 * 
 * Files that were modified:
 * 1. app/Http/Controllers/Customer/EmailVerificationController.php - Removed verifyWithCode method
 * 2. routes/customer.php - Removed /email/verify-code route
 * 3. resources/js/Pages/Auth/VerifyEmail.vue - Removed code input form
 * 
 * Usage: Access via browser or run from command line
 * URL: https://marketsz.com/cpanel_fixes/remove_email_verification_backdoor.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>\n<html>\n<head>\n<title>Remove Email Verification Backdoor</title>\n";
echo "<style>
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 900px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
.container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
h1 { color: #1a1a2e; border-bottom: 3px solid #4a69bd; padding-bottom: 15px; }
h2 { color: #4a69bd; margin-top: 30px; }
.success { color: #27ae60; background: #e8f8f0; padding: 10px 15px; border-radius: 5px; margin: 10px 0; }
.error { color: #e74c3c; background: #fdf0ed; padding: 10px 15px; border-radius: 5px; margin: 10px 0; }
.info { color: #2980b9; background: #ebf5fb; padding: 10px 15px; border-radius: 5px; margin: 10px 0; }
.warning { color: #f39c12; background: #fef9e7; padding: 10px 15px; border-radius: 5px; margin: 10px 0; }
pre { background: #2d3436; color: #dfe6e9; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 13px; }
code { font-family: 'Fira Code', 'Consolas', monospace; }
.file-path { font-family: monospace; background: #ecf0f1; padding: 2px 6px; border-radius: 3px; }
ul { line-height: 1.8; }
.checklist li { list-style: none; padding-left: 5px; }
.checklist li::before { content: '✓ '; color: #27ae60; font-weight: bold; }
</style>\n</head>\n<body>\n<div class='container'>\n";

echo "<h1>🔒 Remove Email Verification Backdoor</h1>\n";

// Define the base path
$basePath = dirname(__DIR__);

echo "<h2>📋 Summary</h2>\n";
echo "<div class='info'>\n";
echo "<p>This fix removes the development backdoor that allowed bypassing email verification using the code \"00000\".</p>\n";
echo "</div>\n";

echo "<h2>📁 Files Modified</h2>\n";
echo "<ul class='checklist'>\n";
echo "<li><span class='file-path'>app/Http/Controllers/Customer/EmailVerificationController.php</span> - Removed <code>verifyWithCode()</code> method</li>\n";
echo "<li><span class='file-path'>routes/customer.php</span> - Removed <code>/email/verify-code</code> route</li>\n";
echo "<li><span class='file-path'>resources/js/Pages/Auth/VerifyEmail.vue</span> - Removed code input form UI</li>\n";
echo "</ul>\n";

// Verify the files have been updated
echo "<h2>✅ Verification</h2>\n";

$controllerPath = $basePath . '/app/Http/Controllers/Customer/EmailVerificationController.php';
$routesPath = $basePath . '/routes/customer.php';

$allGood = true;

// Check controller
if (file_exists($controllerPath)) {
    $controllerContent = file_get_contents($controllerPath);
    if (strpos($controllerContent, 'verifyWithCode') === false && strpos($controllerContent, '00000') === false) {
        echo "<div class='success'>✓ Controller: <code>verifyWithCode</code> method removed successfully</div>\n";
    } else {
        echo "<div class='error'>✗ Controller still contains the backdoor method. Please upload the updated file.</div>\n";
        $allGood = false;
    }
} else {
    echo "<div class='error'>✗ Controller file not found at: $controllerPath</div>\n";
    $allGood = false;
}

// Check routes
if (file_exists($routesPath)) {
    $routesContent = file_get_contents($routesPath);
    if (strpos($routesContent, 'verifyWithCode') === false && strpos($routesContent, 'verification.code') === false) {
        echo "<div class='success'>✓ Routes: Verification code route removed successfully</div>\n";
    } else {
        echo "<div class='error'>✗ Routes still contain the backdoor route. Please upload the updated file.</div>\n";
        $allGood = false;
    }
} else {
    echo "<div class='error'>✗ Routes file not found at: $routesPath</div>\n";
    $allGood = false;
}

// Final status
echo "<h2>🎯 Status</h2>\n";
if ($allGood) {
    echo "<div class='success'><strong>All checks passed!</strong> The email verification backdoor has been removed.</div>\n";
    echo "<div class='info'>\n";
    echo "<p><strong>Email Verification Flow (Production):</strong></p>\n";
    echo "<ol>\n";
    echo "<li>Customer registers → Verification email sent with signed URL</li>\n";
    echo "<li>Customer clicks email link → URL verified via <code>signed</code> middleware</li>\n";
    echo "<li><code>email_verified_at</code> set → Customer gains access to dashboard</li>\n";
    echo "</ol>\n";
    echo "</div>\n";
} else {
    echo "<div class='error'><strong>Some files need to be updated.</strong> Please upload the modified files from your local development environment.</div>\n";
}

echo "<h2>🔧 Manual Verification Commands</h2>\n";
echo "<pre><code># SSH into server and verify files:
grep -r 'verifyWithCode' app/Http/Controllers/Customer/
grep -r '00000' resources/js/Pages/Auth/

# Both should return no results</code></pre>\n";

echo "<h2>⚠️ Important Notes</h2>\n";
echo "<div class='warning'>\n";
echo "<ul>\n";
echo "<li>Make sure to rebuild frontend assets: <code>npm run build</code></li>\n";
echo "<li>Clear Laravel cache: <code>php artisan config:clear && php artisan route:clear</code></li>\n";
echo "<li>If using Vite, the Vue changes require a fresh build to take effect</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "</div>\n</body>\n</html>\n";
