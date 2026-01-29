<?php
/**
 * Migrate Old Customers from Excel to Database
 * 
 * Standalone cPanel script - no Laravel bootstrap required.
 * 
 * Usage:
 *   Browser: https://marketsz.com/cpanel_fixes/migrate_old_customers.php?dry_run=1
 *   CLI:     php migrate_old_customers.php --dry-run
 */

// ============================================
// CONFIGURATION - UPDATE FOR PRODUCTION
// ============================================
$config = [
    'db_host' => 'localhost',  // Use IP for local socket compatibility
    'db_name' => 'miro777_marketszSys',   // UPDATE FOR PRODUCTION
    'db_user' => 'miro777_marketszSys',       // UPDATE FOR PRODUCTION
    'db_pass' => '_O~36]AFlw3l',       // UPDATE FOR PRODUCTION
    'excel_path' => __DIR__ . '/Marketsz Customer Database 7-1-2025.xlsx',
    'default_warehouse_id' => 1, // Production warehouse ID
];

// ============================================
// COUNTRY CODE MAPPING
// ============================================
$countryMap = [
    'Curacao' => 'CW',
    'Curaçao' => 'CW',
    'St. Maarten' => 'SX',
    'Sint Maarten' => 'SX',
    'Bonaire' => 'BQ-BO',
    'St. Eustatius' => 'BQ-SE',
    'Sint Eustatius' => 'BQ-SE',
    'Saba' => 'BQ-SA',
];

// ============================================
// DETECT DRY RUN MODE
// ============================================
$isDryRun = isset($_GET['dry_run']) || in_array('--dry-run', $argv ?? []);

// ============================================
// OUTPUT HELPERS
// ============================================
$isCli = php_sapi_name() === 'cli';
function output($message, $type = 'info')
{
    global $isCli;
    $colors = ['info' => '36', 'success' => '32', 'warning' => '33', 'error' => '31'];
    if ($isCli) {
        echo "\033[{$colors[$type]}m{$message}\033[0m\n";
    } else {
        $htmlColors = ['info' => '#0099cc', 'success' => '#28a745', 'warning' => '#ffc107', 'error' => '#dc3545'];
        echo "<div style='color:{$htmlColors[$type]};font-family:monospace;'>{$message}</div>";
    }
}

if (!$isCli) {
    echo "<html><head><title>Customer Migration</title></head><body style='background:#1a1a2e;color:#eee;padding:20px;'>";
    echo "<h1>🔄 Customer Migration Tool</h1>";
    echo "<p><strong>Mode:</strong> " . ($isDryRun ? "DRY RUN (no changes)" : "LIVE MIGRATION") . "</p><hr>";
}

output("Starting migration... " . ($isDryRun ? "(DRY RUN)" : "(LIVE)"), 'info');

// ============================================
// LOAD PHPSPREADSHEET
// ============================================
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
];
$loaded = false;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $loaded = true;
        break;
    }
}
if (!$loaded) {
    output("ERROR: vendor/autoload.php not found!", 'error');
    exit(1);
}

use PhpOffice\PhpSpreadsheet\IOFactory;

// ============================================
// CONNECT TO DATABASE
// ============================================
try {
    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'],
        $config['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    output("✓ Database connected", 'success');
} catch (PDOException $e) {
    output("ERROR: Database connection failed - " . $e->getMessage(), 'error');
    exit(1);
}

// ============================================
// LOAD EXCEL FILE
// ============================================
if (!file_exists($config['excel_path'])) {
    output("ERROR: Excel file not found at {$config['excel_path']}", 'error');
    exit(1);
}

try {
    $spreadsheet = IOFactory::load($config['excel_path']);
    $sheet = $spreadsheet->getActiveSheet();
    $data = $sheet->toArray();
    output("✓ Excel loaded: " . (count($data) - 1) . " records", 'success');
} catch (Exception $e) {
    output("ERROR: Failed to load Excel - " . $e->getMessage(), 'error');
    exit(1);
}

// ============================================
// GET EXISTING EMAILS & SUITES
// ============================================
$existingEmails = [];
$existingSuites = [];
$stmt = $pdo->query("SELECT email, suite FROM customers WHERE deleted_at IS NULL");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $existingEmails[strtolower($row['email'])] = true;
    $existingSuites[$row['suite']] = true;
}
output("✓ Existing customers: " . count($existingEmails), 'info');

// ============================================
// PROCESS RECORDS
// ============================================
$headers = array_map('trim', $data[0]);
$records = array_slice($data, 1);

// Group by email to handle duplicates (keep highest suite)
$grouped = [];
foreach ($records as $row) {
    $record = array_combine($headers, $row);
    $email = strtolower(trim($record['Email'] ?? ''));
    if (empty($email))
        continue;

    $suite = $record['Suite'] ?? '';
    if (!isset($grouped[$email]) || $suite > $grouped[$email]['Suite']) {
        $grouped[$email] = $record;
    }
}

$stats = [
    'total' => count($records),
    'duplicates_skipped' => count($records) - count($grouped),
    'already_exists' => 0,
    'suite_conflict' => 0,
    'migrated' => 0,
    'errors' => 0,
];

output("Processing " . count($grouped) . " unique emails...", 'info');

// Prepare insert statement
$insertSql = "INSERT INTO customers (
    first_name, last_name, email, phone, suite, country, 
    warehouse_id, is_active, is_old, email_verified_at, password,
    created_at, updated_at
) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1, NOW(), ?, NOW(), NOW())";
$insertStmt = $pdo->prepare($insertSql);

// Random password hash (will need reset)
$randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);

foreach ($grouped as $email => $record) {
    // Check if already exists
    if (isset($existingEmails[$email])) {
        $stats['already_exists']++;
        continue;
    }

    // Check suite conflict
    $suite = $record['Suite'] ?? '';
    if (isset($existingSuites[$suite])) {
        $stats['suite_conflict']++;
        output("⚠ Suite conflict: {$suite} ({$email})", 'warning');
        continue;
    }

    // Split name
    $fullName = trim($record['Full Name'] ?? '');
    $parts = preg_split('/\s+/', $fullName);
    $lastName = array_pop($parts) ?: $fullName;
    $firstName = implode(' ', $parts) ?: $lastName;

    // Normalize country
    $countryRaw = trim($record['Country'] ?? '');
    $country = $countryMap[$countryRaw] ?? $countryRaw;

    // Phone
    $phone = trim($record['Phone'] ?? '');

    if (!$isDryRun) {
        try {
            $insertStmt->execute([
                $firstName,
                $lastName,
                $email,
                $phone,
                $suite,
                $country,
                $config['default_warehouse_id'],
                $randomPassword,
            ]);
            $stats['migrated']++;
            $existingEmails[$email] = true;
            $existingSuites[$suite] = true;
        } catch (PDOException $e) {
            $stats['errors']++;
            output("✗ Error inserting {$email}: " . $e->getMessage(), 'error');
        }
    } else {
        $stats['migrated']++;
    }
}

// ============================================
// SUMMARY
// ============================================
echo $isCli ? "\n" : "<hr>";
output("=== MIGRATION SUMMARY ===", 'info');
output("Total records in Excel: {$stats['total']}", 'info');
output("Duplicates skipped: {$stats['duplicates_skipped']}", 'warning');
output("Already in database: {$stats['already_exists']}", 'info');
output("Suite conflicts: {$stats['suite_conflict']}", $stats['suite_conflict'] > 0 ? 'warning' : 'info');
output("Migrated: {$stats['migrated']}" . ($isDryRun ? " (DRY RUN)" : ""), 'success');
output("Errors: {$stats['errors']}", $stats['errors'] > 0 ? 'error' : 'info');

if ($isDryRun) {
    echo $isCli ? "\n" : "<br>";
    output("🔔 This was a DRY RUN. No data was modified.", 'warning');
    output("Run without --dry-run or ?dry_run to execute the migration.", 'info');
}

if (!$isCli) {
    echo "</body></html>";
}
