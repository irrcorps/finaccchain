<?php
/**
 * Minimal PDO singleton wrapper. Kept dependency-free on purpose.
 */

class Database
{
    private static $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            require_once __DIR__ . '/../config/database.php';
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                die('Koneksi database gagal. Pastikan MySQL aktif dan database "finaccchain" sudah diimpor dari /database/schema.sql. Detail: ' . htmlspecialchars($e->getMessage()));
            }
        }
        return self::$instance;
    }
}
