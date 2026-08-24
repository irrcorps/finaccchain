<?php
/**
 * Lightweight health check for hosting platform uptime monitors.
 * Deliberately does not require full bootstrap/session to stay fast and
 * to avoid leaking any credential/server detail on failure.
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain; charset=utf-8');

$dbStatus = 'error';
try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $options = [PDO::ATTR_TIMEOUT => 3, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
    if (DB_SSL_MODE !== '' && defined('PDO::MYSQL_ATTR_SSL_CA')) {
        if (DB_SSL_CA !== '') $options[PDO::MYSQL_ATTR_SSL_CA] = DB_SSL_CA;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = DB_SSL_MODE !== 'require';
    }
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    $pdo->query('SELECT 1');
    $dbStatus = 'connected';
} catch (Throwable $e) {
    $dbStatus = 'error';
    http_response_code(503);
}

echo "application=FinAccChain\n";
echo 'status=' . ($dbStatus === 'connected' ? 'ok' : 'degraded') . "\n";
echo "database=$dbStatus\n";
