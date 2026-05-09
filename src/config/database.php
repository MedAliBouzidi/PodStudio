<?php

class Database
{
    private static string $host;
    private static string $dbname;
    private static string $username;
    private static string $password;
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            try {
                $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=utf8mb4";
                self::$instance = new PDO($dsn, self::$username, self::$password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$instance;
    }

    private function __construct()
    {
        self::$host = getenv("DB_HOST") ?: "localhost";
        self::$dbname = getenv("DB_NAME") ?: "pod_studio";
        self::$username = getenv("DB_USER") ?: "root";
        self::$password = getenv("DB_PASSWORD") ?: "";
    }
    private function __clone()
    {
    }
}