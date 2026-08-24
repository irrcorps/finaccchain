<?php
/**
 * Front-controller style bootstrap. Every entry-point page requires this file.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/HashChain.php';
require_once __DIR__ . '/RuleEngine.php';
require_once __DIR__ . '/Accounting.php';
require_once __DIR__ . '/Accountability.php';
require_once __DIR__ . '/TransactionService.php';
require_once __DIR__ . '/ReportBuilder.php';

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

error_reporting(E_ALL);
ini_set('display_errors', '1');
