<?php
/**
 * FinAccChain - global application configuration
 * Research prototype (TKT 3) - not a production financial system.
 */

define('APP_NAME', 'FinAccChain');
define('APP_TAGLINE', 'Smart Financial Accountability for MSMEs');
// APP_ENV: 'research-prototype' (default/local) or 'production' (set via env
// on the hosting platform) - only toggles error display below, nothing else.
define('APP_ENV', getenv('APP_ENV') ?: 'research-prototype');
define('APP_VERSION', '0.1.0-tkt3');

define('APP_ROOT', dirname(__DIR__));

// Base URL: if APP_URL is set (e.g. on Render), use it directly. Otherwise
// auto-detect the app's install path relative to the document root - same
// on every page regardless of how deep the current script is nested.
$envAppUrl = getenv('APP_URL');
if ($envAppUrl) {
    define('APP_URL_BASE', rtrim($envAppUrl, '/'));
} else {
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']) ?: '') : '';
    $appRoot = str_replace('\\', '/', realpath(APP_ROOT) ?: '');
    $base = '';
    if ($docRoot !== '' && $appRoot !== '' && strpos($appRoot, $docRoot) === 0) {
        $base = substr($appRoot, strlen($docRoot));
    }
    define('APP_URL_BASE', rtrim($base, '/'));
}
define('UPLOAD_DIR', APP_ROOT . '/uploads/evidence');
define('UPLOAD_MAX_BYTES', 3 * 1024 * 1024); // 3MB
define('ALLOWED_EVIDENCE_TYPES', ['jpg', 'jpeg', 'png', 'pdf']);

define('RESEARCH_DISCLAIMER', 'Prototipe riset TKT 3: rule engine dan hash-chain di bawah ini adalah SIMULASI deterministik untuk mendemonstrasikan model integrasi fintech-smart contract. Sistem ini TIDAK terhubung ke jaringan blockchain (mainnet/testnet) maupun API fintech nyata.');

date_default_timezone_set('Asia/Jakarta');
