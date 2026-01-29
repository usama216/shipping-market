<?php
/**
 * Installer Helper Functions
 */

/**
 * Check all system requirements
 */
function checkRequirements(): array
{
    $requirements = [];

    // PHP Version
    $requirements[] = [
        'name' => 'PHP Version',
        'required' => '8.2+',
        'current' => PHP_VERSION,
        'passed' => version_compare(PHP_VERSION, '8.2.0', '>='),
    ];

    // Required Extensions
    $extensions = ['ctype', 'curl', 'dom', 'fileinfo', 'gd', 'intl', 'mbstring', 'pdo', 'pdo_mysql', 'tokenizer', 'xml', 'zip'];
    foreach ($extensions as $ext) {
        $requirements[] = [
            'name' => "ext-{$ext}",
            'required' => 'Enabled',
            'current' => extension_loaded($ext) ? 'Enabled' : 'Missing',
            'passed' => extension_loaded($ext),
        ];
    }

    // Writable Directories
    $writableDirs = ['storage', 'storage/app', 'storage/framework', 'storage/logs', 'bootstrap/cache'];
    foreach ($writableDirs as $dir) {
        $path = __DIR__ . '/../' . $dir;
        $writable = is_writable($path);
        $requirements[] = [
            'name' => "{$dir}/",
            'required' => 'Writable',
            'current' => $writable ? 'Writable' : 'Not Writable',
            'passed' => $writable,
        ];
    }

    // .env file
    $envPath = __DIR__ . '/../.env';
    $envExists = file_exists($envPath);
    $envWritable = $envExists ? is_writable($envPath) : is_writable(dirname($envPath));
    $requirements[] = [
        'name' => '.env file',
        'required' => 'Writable',
        'current' => $envWritable ? 'Writable' : 'Not Writable',
        'passed' => $envWritable,
    ];

    return $requirements;
}

/**
 * Test database connection
 */
function testDatabaseConnection(string $host, string $port, string $database, string $username, string $password): array
{
    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$database}";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Generate Laravel-compatible app key
 */
function generateAppKey(): string
{
    return 'base64:' . base64_encode(random_bytes(32));
}

/**
 * Write .env file
 */
function writeEnvFile(array $config): bool
{
    $envPath = __DIR__ . '/../.env';
    $examplePath = __DIR__ . '/../.env.example';

    // Read example file
    if (!file_exists($examplePath)) {
        return false;
    }

    $content = file_get_contents($examplePath);

    // Replace values
    $replacements = [
        'APP_NAME' => $config['app_name'] ?? 'Marketsz',
        'APP_ENV' => 'production',
        'APP_KEY' => $config['app_key'],
        'APP_DEBUG' => 'false',
        'APP_URL' => $config['app_url'],
        'DB_CONNECTION' => 'mysql',
        'DB_HOST' => $config['db_host'],
        'DB_PORT' => $config['db_port'],
        'DB_DATABASE' => $config['db_database'],
        'DB_USERNAME' => $config['db_username'],
        'DB_PASSWORD' => $config['db_password'],
    ];

    foreach ($replacements as $key => $value) {
        // Handle commented-out keys
        $content = preg_replace("/^#?\s*{$key}=.*/m", "{$key}={$value}", $content);
    }

    return file_put_contents($envPath, $content) !== false;
}

/**
 * Run migrations using artisan
 */
function runMigrations(): array
{
    $artisan = __DIR__ . '/../artisan';

    // Clear any cached config
    exec("php {$artisan} config:clear 2>&1", $output, $code);

    // Run migrations
    exec("php {$artisan} migrate --force 2>&1", $output, $code);

    if ($code !== 0) {
        return ['success' => false, 'error' => implode("\n", $output)];
    }

    return ['success' => true];
}

/**
 * Run database seeders
 */
function runSeeders(): array
{
    $artisan = __DIR__ . '/../artisan';

    exec("php {$artisan} db:seed --force 2>&1", $output, $code);

    if ($code !== 0) {
        return ['success' => false, 'error' => implode("\n", $output)];
    }

    return ['success' => true];
}

/**
 * Create admin user
 */
function createAdminUser(string $name, string $email, string $password): array
{
    // Read database config from .env
    $envPath = __DIR__ . '/../.env';
    $envContent = file_get_contents($envPath);

    // Parse .env values
    preg_match('/^DB_HOST=(.*)$/m', $envContent, $hostMatch);
    preg_match('/^DB_PORT=(.*)$/m', $envContent, $portMatch);
    preg_match('/^DB_DATABASE=(.*)$/m', $envContent, $dbMatch);
    preg_match('/^DB_USERNAME=(.*)$/m', $envContent, $userMatch);
    preg_match('/^DB_PASSWORD=(.*)$/m', $envContent, $passMatch);

    $dbHost = trim($hostMatch[1] ?? 'localhost');
    $dbPort = trim($portMatch[1] ?? '3306');
    $dbName = trim($dbMatch[1] ?? '');
    $dbUser = trim($userMatch[1] ?? '');
    $dbPass = trim($passMatch[1] ?? '');

    try {
        $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName}";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        // Hash password using PHP's password_hash (bcrypt)
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        // Split name into first_name and last_name
        $nameParts = explode(' ', $name, 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users (first_name, last_name, email, password, email_verified_at, type, is_active, created_at, updated_at)
            VALUES (:first_name, :last_name, :email, :password, NOW(), 1, 1, NOW(), NOW())
        ");
        $stmt->execute([
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':email' => $email,
            ':password' => $hashedPassword,
        ]);

        $userId = $pdo->lastInsertId();

        // Get super-admin role ID
        $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'super-admin' LIMIT 1");
        $roleStmt->execute();
        $role = $roleStmt->fetch(PDO::FETCH_ASSOC);

        if ($role) {
            // Assign role to user
            $assignStmt = $pdo->prepare("
                INSERT INTO model_has_roles (role_id, model_type, model_id)
                VALUES (:role_id, 'App\\\\Models\\\\User', :user_id)
            ");
            $assignStmt->execute([
                ':role_id' => $role['id'],
                ':user_id' => $userId,
            ]);
        }

        return ['success' => true];

    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Create installation lock file
 */
function createInstallLock(): bool
{
    $lockFile = __DIR__ . '/../storage/installed';
    return file_put_contents($lockFile, date('Y-m-d H:i:s')) !== false;
}

/**
 * Handle database configuration step
 */
function handleDatabaseStep(array $data): array
{
    $host = trim($data['db_host'] ?? '');
    $port = trim($data['db_port'] ?? '3306');
    $database = trim($data['db_database'] ?? '');
    $username = trim($data['db_username'] ?? '');
    $password = $data['db_password'] ?? '';
    $appUrl = trim($data['app_url'] ?? '');
    $appName = trim($data['app_name'] ?? 'Marketsz');

    // Validate required fields
    if (empty($host) || empty($database) || empty($username) || empty($appUrl)) {
        return ['success' => false, 'error' => 'All fields except password are required.'];
    }

    // Test database connection
    $testResult = testDatabaseConnection($host, $port, $database, $username, $password);
    if (!$testResult['success']) {
        return ['success' => false, 'error' => 'Database connection failed: ' . $testResult['error']];
    }

    // Generate app key and write .env
    $config = [
        'app_name' => $appName,
        'app_key' => generateAppKey(),
        'app_url' => $appUrl,
        'db_host' => $host,
        'db_port' => $port,
        'db_database' => $database,
        'db_username' => $username,
        'db_password' => $password,
    ];

    if (!writeEnvFile($config)) {
        return ['success' => false, 'error' => 'Failed to write .env file.'];
    }

    // Store in session for later use
    $_SESSION['config'] = $config;

    return ['success' => true];
}

/**
 * Download composer.phar from official source
 */
function downloadComposer(): array
{
    $composerPath = __DIR__ . '/../composer.phar';

    // Skip if already exists
    if (file_exists($composerPath)) {
        return ['success' => true, 'message' => 'Composer already exists'];
    }

    // Download composer installer
    $installerUrl = 'https://getcomposer.org/installer';
    $installer = @file_get_contents($installerUrl);

    if ($installer === false) {
        // Try with cURL as fallback
        if (function_exists('curl_init')) {
            $ch = curl_init($installerUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            $installer = curl_exec($ch);
            curl_close($ch);
        }
    }

    if (empty($installer)) {
        return ['success' => false, 'error' => 'Failed to download Composer installer. Check internet connection.'];
    }

    // Save and run installer
    $installerPath = __DIR__ . '/../composer-setup.php';
    file_put_contents($installerPath, $installer);

    $projectRoot = realpath(__DIR__ . '/..');
    exec("cd {$projectRoot} && php composer-setup.php 2>&1", $output, $code);

    // Clean up installer
    @unlink($installerPath);

    if (!file_exists($composerPath)) {
        return ['success' => false, 'error' => 'Composer installation failed: ' . implode("\n", $output)];
    }

    return ['success' => true];
}

/**
 * Run composer install
 */
function runComposerInstall(): array
{
    $projectRoot = realpath(__DIR__ . '/..');
    $composerPath = $projectRoot . '/composer.phar';

    if (!file_exists($composerPath)) {
        return ['success' => false, 'error' => 'composer.phar not found'];
    }

    // Set memory limit and run composer
    $command = "cd {$projectRoot} && php -d memory_limit=512M composer.phar install --no-dev --optimize-autoloader --no-interaction 2>&1";

    exec($command, $output, $code);

    if ($code !== 0) {
        return ['success' => false, 'error' => implode("\n", $output)];
    }

    return ['success' => true];
}

/**
 * Handle dependencies installation step
 */
function handleDependenciesStep(): array
{
    // Check if vendor already exists (skip composer if pre-built)
    $vendorPath = __DIR__ . '/../vendor/autoload.php';

    if (!file_exists($vendorPath)) {
        // Download composer if needed
        $downloadResult = downloadComposer();
        if (!$downloadResult['success']) {
            return $downloadResult;
        }

        // Run composer install
        $composerResult = runComposerInstall();
        if (!$composerResult['success']) {
            return ['success' => false, 'error' => 'Composer install failed: ' . $composerResult['error']];
        }
    }

    // Run migrations
    $migrationResult = runMigrations();
    if (!$migrationResult['success']) {
        return ['success' => false, 'error' => 'Migration failed: ' . $migrationResult['error']];
    }

    // Run seeders
    $seederResult = runSeeders();
    if (!$seederResult['success']) {
        return ['success' => false, 'error' => 'Seeding failed: ' . $seederResult['error']];
    }

    // Clean up composer.phar to save space
    $composerPath = __DIR__ . '/../composer.phar';
    if (file_exists($composerPath)) {
        @unlink($composerPath);
    }

    return ['success' => true];
}

/**
 * Handle admin user creation step
 */
function handleAdminStep(array $data): array
{
    $name = trim($data['admin_name'] ?? '');
    $email = trim($data['admin_email'] ?? '');
    $password = $data['admin_password'] ?? '';
    $passwordConfirm = $data['admin_password_confirm'] ?? '';

    // Validate
    if (empty($name) || empty($email) || empty($password)) {
        return ['success' => false, 'error' => 'All fields are required.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Invalid email address.'];
    }

    if (strlen($password) < 8) {
        return ['success' => false, 'error' => 'Password must be at least 8 characters.'];
    }

    if ($password !== $passwordConfirm) {
        return ['success' => false, 'error' => 'Passwords do not match.'];
    }

    // Create admin user
    $result = createAdminUser($name, $email, $password);
    if (!$result['success']) {
        return ['success' => false, 'error' => 'Failed to create admin: ' . $result['error']];
    }

    // Create install lock
    createInstallLock();

    return ['success' => true];
}
