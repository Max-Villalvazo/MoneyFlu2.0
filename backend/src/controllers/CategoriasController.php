<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Response.php';

class CategoriasController
{
    public static function index(): void
    {
        $userId = Auth::userId();
        $entorno = $_GET['entorno'] ?? null;

        if (!in_array($entorno, ['personal', 'negocio'], true)) {
            Response::error('Entorno inválido. Usa "personal" o "negocio".');
            return;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT id, nombre, tipo, entorno FROM categorias
             WHERE user_id = :user_id AND entorno = :entorno
             ORDER BY tipo, nombre'
        );
        $stmt->execute(['user_id' => $userId, 'entorno' => $entorno]);

        Response::success(['categorias' => $stmt->fetchAll()]);
    }

    public static function store(array $body): void
    {
        $userId = Auth::userId();
        $nombre = trim($body['nombre'] ?? '');
        $tipo = $body['tipo'] ?? '';
        $entorno = $body['entorno'] ?? '';

        if ($nombre === '') {
            Response::error('El nombre de la categoría es obligatorio.');
            return;
        }
        if (!in_array($tipo, ['ingreso', 'gasto'], true)) {
            Response::error('Tipo inválido. Usa "ingreso" o "gasto".');
            return;
        }
        if (!in_array($entorno, ['personal', 'negocio'], true)) {
            Response::error('Entorno inválido. Usa "personal" o "negocio".');
            return;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO categorias (user_id, nombre, tipo, entorno) VALUES (:user_id, :nombre, :tipo, :entorno)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'nombre' => $nombre,
            'tipo' => $tipo,
            'entorno' => $entorno,
        ]);

        Response::success(['id' => (int) $db->lastInsertId()], 'Categoría creada.');
    }

    public static function destroy(int $id): void
    {
        $userId = Auth::userId();
        $db = Database::getConnection();

        // Solo se puede borrar una categoría propia
        $stmt = $db->prepare('DELETE FROM categorias WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);

        if ($stmt->rowCount() === 0) {
            Response::error('Categoría no encontrada.', 404);
            return;
        }

        Response::success([], 'Categoría eliminada.');
    }
}
