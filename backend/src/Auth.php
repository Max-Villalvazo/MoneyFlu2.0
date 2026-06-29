<?php

/**
 * Clase Auth
 * Maneja el inicio de sesión PHP y la verificación de usuario autenticado.
 * Usamos sesiones nativas (cookie PHPSESSID) en lugar de JWT.
 */
class Auth
{
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login(int $userId, string $nombre, string $email): void
    {
        self::startSession();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['nombre'] = $nombre;
        $_SESSION['email'] = $email;
    }

    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        session_destroy();
    }

    public static function isLoggedIn(): bool
    {
        self::startSession();
        return isset($_SESSION['user_id']);
    }

    public static function userId(): ?int
    {
        self::startSession();
        return $_SESSION['user_id'] ?? null;
    }

    public static function currentUser(): ?array
    {
        self::startSession();
        if (!self::isLoggedIn()) {
            return null;
        }
        return [
            'id' => $_SESSION['user_id'],
            'nombre' => $_SESSION['nombre'],
            'email' => $_SESSION['email'],
        ];
    }

    /**
     * Corta la ejecución si el usuario no está logueado.
     */
    public static function requireAuth(): void
    {
        if (!self::isLoggedIn()) {
            Response::error('No autenticado. Inicia sesión primero.', 401);
            exit;
        }
    }
}
