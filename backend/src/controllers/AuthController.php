<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Response.php';

class AuthController
{
    public static function register(array $body): void
    {
        $nombre = trim($body['nombre'] ?? '');
        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        if ($nombre === '' || $email === '' || $password === '') {
            Response::error('Nombre, email y contraseña son obligatorios.');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('El email no es válido.');
            return;
        }

        if (strlen($password) < 6) {
            Response::error('La contraseña debe tener al menos 6 caracteres.');
            return;
        }

        $db = Database::getConnection();

        // Verificar que el email no exista ya
        $stmt = $db->prepare('SELECT id FROM usuarios WHERE email = :email');
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            Response::error('Ya existe una cuenta con ese email.', 409);
            return;
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                'INSERT INTO usuarios (nombre, email, password) VALUES (:nombre, :email, :password)'
            );
            $stmt->execute([
                'nombre' => $nombre,
                'email' => $email,
                'password' => $hashedPassword,
            ]);

            $userId = (int) $db->lastInsertId();

            // Crear categorías por defecto para que el usuario no empiece en blanco
            self::crearCategoriasPorDefecto($db, $userId);

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            Response::error('No se pudo crear la cuenta.', 500);
            return;
        }

        Auth::login($userId, $nombre, $email);

        Response::success([
            'usuario' => ['id' => $userId, 'nombre' => $nombre, 'email' => $email],
        ], 'Cuenta creada correctamente.');
    }

    public static function login(array $body): void
    {
        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        if ($email === '' || $password === '') {
            Response::error('Email y contraseña son obligatorios.');
            return;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id, nombre, email, password FROM usuarios WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch();

        if (!$usuario || !password_verify($password, $usuario['password'])) {
            Response::error('Email o contraseña incorrectos.', 401);
            return;
        }

        Auth::login((int) $usuario['id'], $usuario['nombre'], $usuario['email']);

        Response::success([
            'usuario' => [
                'id' => $usuario['id'],
                'nombre' => $usuario['nombre'],
                'email' => $usuario['email'],
            ],
        ], 'Sesión iniciada correctamente.');
    }

    public static function logout(): void
    {
        Auth::logout();
        Response::success([], 'Sesión cerrada.');
    }

    public static function me(): void
    {
        $user = Auth::currentUser();
        if (!$user) {
            Response::error('No autenticado.', 401);
            return;
        }
        Response::success(['usuario' => $user]);
    }

    private static function crearCategoriasPorDefecto(PDO $db, int $userId): void
    {
        $categorias = [
            // Personal
            ['nombre' => 'Sueldo', 'tipo' => 'ingreso', 'entorno' => 'personal'],
            ['nombre' => 'Comida', 'tipo' => 'gasto', 'entorno' => 'personal'],
            ['nombre' => 'Transporte', 'tipo' => 'gasto', 'entorno' => 'personal'],
            ['nombre' => 'Entretenimiento', 'tipo' => 'gasto', 'entorno' => 'personal'],
            // Negocio
            ['nombre' => 'Ventas', 'tipo' => 'ingreso', 'entorno' => 'negocio'],
            ['nombre' => 'Insumos', 'tipo' => 'gasto', 'entorno' => 'negocio'],
            ['nombre' => 'Renta', 'tipo' => 'gasto', 'entorno' => 'negocio'],
            ['nombre' => 'Publicidad', 'tipo' => 'gasto', 'entorno' => 'negocio'],
        ];

        $stmt = $db->prepare(
            'INSERT INTO categorias (user_id, nombre, tipo, entorno) VALUES (:user_id, :nombre, :tipo, :entorno)'
        );

        foreach ($categorias as $cat) {
            $stmt->execute([
                'user_id' => $userId,
                'nombre' => $cat['nombre'],
                'tipo' => $cat['tipo'],
                'entorno' => $cat['entorno'],
            ]);
        }
    }
}
