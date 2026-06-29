<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Response.php';

class TransaccionesController
{
    /**
     * Lista transacciones del usuario, filtradas por entorno y opcionalmente por mes (YYYY-MM).
     */
    public static function index(): void
    {
        $userId = Auth::userId();
        $entorno = $_GET['entorno'] ?? null;
        $mes = $_GET['mes'] ?? null; // formato esperado: YYYY-MM

        if (!in_array($entorno, ['personal', 'negocio'], true)) {
            Response::error('Entorno inválido. Usa "personal" o "negocio".');
            return;
        }

        $db = Database::getConnection();

        $sql = 'SELECT t.id, t.monto, t.descripcion, t.fecha, t.tipo, t.entorno,
                       c.id AS categoria_id, c.nombre AS categoria_nombre
                FROM transacciones t
                JOIN categorias c ON c.id = t.categoria_id
                WHERE t.user_id = :user_id AND t.entorno = :entorno';

        $params = ['user_id' => $userId, 'entorno' => $entorno];

        if ($mes && preg_match('/^\d{4}-\d{2}$/', $mes)) {
            $sql .= ' AND DATE_FORMAT(t.fecha, "%Y-%m") = :mes';
            $params['mes'] = $mes;
        }

        $sql .= ' ORDER BY t.fecha DESC, t.id DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        Response::success(['transacciones' => $stmt->fetchAll()]);
    }

    public static function store(array $body): void
    {
        $userId = Auth::userId();

        $categoriaId = (int) ($body['categoria_id'] ?? 0);
        $monto = $body['monto'] ?? null;
        $descripcion = trim($body['descripcion'] ?? '');
        $fecha = $body['fecha'] ?? '';
        $tipo = $body['tipo'] ?? '';
        $entorno = $body['entorno'] ?? '';

        if ($categoriaId <= 0) {
            Response::error('Debes seleccionar una categoría.');
            return;
        }
        if (!is_numeric($monto) || (float) $monto <= 0) {
            Response::error('El monto debe ser un número mayor a 0.');
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
        if (!self::esFechaValida($fecha)) {
            Response::error('La fecha no es válida. Usa formato YYYY-MM-DD.');
            return;
        }

        $db = Database::getConnection();

        // Verificar que la categoría pertenezca al usuario y coincida con el entorno
        $stmt = $db->prepare(
            'SELECT id FROM categorias WHERE id = :id AND user_id = :user_id AND entorno = :entorno'
        );
        $stmt->execute(['id' => $categoriaId, 'user_id' => $userId, 'entorno' => $entorno]);
        if (!$stmt->fetch()) {
            Response::error('La categoría no existe o no corresponde a este entorno.', 422);
            return;
        }

        $stmt = $db->prepare(
            'INSERT INTO transacciones (user_id, categoria_id, tipo, monto, descripcion, fecha, entorno)
             VALUES (:user_id, :categoria_id, :tipo, :monto, :descripcion, :fecha, :entorno)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'categoria_id' => $categoriaId,
            'tipo' => $tipo,
            'monto' => (float) $monto,
            'descripcion' => $descripcion,
            'fecha' => $fecha,
            'entorno' => $entorno,
        ]);

        Response::success(['id' => (int) $db->lastInsertId()], 'Movimiento registrado.');
    }

    public static function destroy(int $id): void
    {
        $userId = Auth::userId();
        $db = Database::getConnection();

        $stmt = $db->prepare('DELETE FROM transacciones WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);

        if ($stmt->rowCount() === 0) {
            Response::error('Movimiento no encontrado.', 404);
            return;
        }

        Response::success([], 'Movimiento eliminado.');
    }

    private static function esFechaValida(string $fecha): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }
}
