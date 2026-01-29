<?php
/**
 * Marketsz Web Installer
 * 
 * Standalone installer for cPanel/shared hosting deployment.
 * No Laravel dependencies - runs before Laravel is configured.
 */

// Prevent direct access after installation
$installedFile = __DIR__ . '/../storage/installed';
if (file_exists($installedFile)) {
    header('Location: ../');
    exit;
}

// Start session for multi-step wizard
session_start();

// Include helper functions
require_once __DIR__ . '/helpers.php';

// Determine current step
$step = $_GET['step'] ?? 'welcome';
$allowedSteps = ['welcome', 'requirements', 'database', 'dependencies', 'admin', 'complete'];

if (!in_array($step, $allowedSteps)) {
    $step = 'welcome';
}

// Handle POST requests
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 'database':
            $result = handleDatabaseStep($_POST);
            if ($result['success']) {
                $_SESSION['db_configured'] = true;
                header('Location: ?step=dependencies');
                exit;
            }
            $error = $result['error'];
            break;

        case 'dependencies':
            $result = handleDependenciesStep();
            if ($result['success']) {
                $_SESSION['dependencies_installed'] = true;
                header('Location: ?step=admin');
                exit;
            }
            $error = $result['error'];
            break;

        case 'admin':
            $result = handleAdminStep($_POST);
            if ($result['success']) {
                $_SESSION['admin_created'] = true;
                header('Location: ?step=complete');
                exit;
            }
            $error = $result['error'];
            break;
    }
}

// Validate step progression
if ($step === 'database' && empty($_SESSION['requirements_passed'])) {
    header('Location: ?step=requirements');
    exit;
}
if ($step === 'dependencies' && empty($_SESSION['db_configured'])) {
    header('Location: ?step=database');
    exit;
}
if ($step === 'admin' && empty($_SESSION['dependencies_installed'])) {
    header('Location: ?step=dependencies');
    exit;
}
if ($step === 'complete' && empty($_SESSION['admin_created'])) {
    header('Location: ?step=admin');
    exit;
}

// Prepare view data
$viewData = [
    'step' => $step,
    'error' => $error,
    'success' => $success,
];

if ($step === 'requirements') {
    $viewData['requirements'] = checkRequirements();
    $viewData['allPassed'] = !in_array(false, array_column($viewData['requirements'], 'passed'));

    if ($viewData['allPassed']) {
        $_SESSION['requirements_passed'] = true;
    }
}

// Render view
include __DIR__ . '/views/layout.php';
