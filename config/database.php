<?php
/**
 * Database connection settings.
 *
 * Reads from environment variables first (set these in Render / TiDB Cloud
 * for production), falling back to local XAMPP defaults so nothing changes
 * for local development.
 */

function env_or(string $key, $default)
{
    $v = getenv($key);
    return ($v === false || $v === '') ? $default : $v;
}

define('DB_HOST', env_or('DB_HOST', 'localhost'));
define('DB_PORT', env_or('DB_PORT', '3306'));
define('DB_NAME', env_or('DB_DATABASE', 'finaccchain'));
define('DB_USER', env_or('DB_USERNAME', 'root'));
define('DB_PASS', env_or('DB_PASSWORD', ''));
define('DB_CHARSET', 'utf8mb4');

// TiDB Cloud requires TLS. DB_SSL_MODE=require enables it; DB_SSL_CA can
// point to a bundled CA file (TiDB Cloud's public endpoints work with the
// system CA bundle, so this is normally left empty).
define('DB_SSL_MODE', env_or('DB_SSL_MODE', ''));
define('DB_SSL_CA', env_or('DB_SSL_CA', ''));
