<?php
/**
 * Minimal PDO singleton wrapper. Kept dependency-free on purpose.
 * Supports optional TLS (required by TiDB Cloud) via DB_SSL_MODE/DB_SSL_CA.
 */

class Database
{
    private static $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            require_once __DIR__ . '/../config/database.php';
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            if (DB_SSL_MODE === 'require' || DB_SSL_MODE === 'verify-ca' || DB_SSL_MODE === 'verify-full') {
                if (defined('PDO::MYSQL_ATTR_SSL_CA')) {
                    if (DB_SSL_CA !== '') {
                        $options[PDO::MYSQL_ATTR_SSL_CA] = DB_SSL_CA;
                    }
                    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = DB_SSL_MODE !== 'require';
                }
            }
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                http_response_code(500);
                if (defined('APP_ENV') && APP_ENV === 'production') {
                    die('Koneksi database gagal. Silakan hubungi administrator.');
                }
                die('Koneksi database gagal. Pastikan database sudah diimpor (lihat /database). Detail: ' . htmlspecialchars($e->getMessage()));
            }
        }
        return self::$instance;
    }
}
