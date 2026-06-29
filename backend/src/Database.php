<?php

/**
 * Clase Database
 * Maneja la conexión única (singleton) a la base de datos vía PDO.
 */
class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host = getenv('DB_HOST') ?: 'db';
            $dbname = getenv('DB_NAME') ?: 'finanzas_db';
            $user = getenv('DB_USER') ?: 'finanzas_user';
            $pass = getenv('DB_PASS') ?: 'finanzas_pass';

            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                Response::error('Error de conexión a la base de datos', 500);
                exit;
            }
        }

        return self::$instance;
    }
}
