<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Response.php';

class DashboardController
{
    /**
     * Devuelve el balance (ingresos, gastos, balance) del mes indicado
     * (o del mes en curso si no se especifica) para el entorno dado.
     */
    public static function balance(): void
    {
        $userId = Auth::userId();
        $entorno = $_GET['entorno'] ?? null;
        $mes = $_GET['mes'] ?? date('Y-m'); // YYYY-MM, default: mes actual

        if (!in_array($entorno, ['personal', 'negocio'], true)) {
            Response::error('Entorno inválido. Usa "personal" o "negocio".');
            return;
        }
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
            Response::error('Mes inválido. Usa formato YYYY-MM.');
            return;
        }

        $db = Database::getConnection();

        $stmt = $db->prepare(
            'SELECT
                COALESCE(SUM(CASE WHEN tipo = "ingreso" THEN monto ELSE 0 END), 0) AS total_ingresos,
                COALESCE(SUM(CASE WHEN tipo = "gasto" THEN monto ELSE 0 END), 0) AS total_gastos
             FROM transacciones
             WHERE user_id = :user_id
               AND entorno = :entorno
               AND DATE_FORMAT(fecha, "%Y-%m") = :mes'
        );
        $stmt->execute(['user_id' => $userId, 'entorno' => $entorno, 'mes' => $mes]);
        $resultado = $stmt->fetch();

        $ingresos = (float) $resultado['total_ingresos'];
        $gastos = (float) $resultado['total_gastos'];

        Response::success([
            'mes' => $mes,
            'entorno' => $entorno,
            'ingresos' => round($ingresos, 2),
            'gastos' => round($gastos, 2),
            'balance' => round($ingresos - $gastos, 2),
        ]);
    }

    /**
     * Devuelve los gastos del mes indicado agrupados por categoría.
     * Pensado para alimentar una gráfica de pastel/dona.
     */
    public static function gastosPorCategoria(): void
    {
        $userId = Auth::userId();
        $entorno = $_GET['entorno'] ?? null;
        $mes = $_GET['mes'] ?? date('Y-m');

        if (!in_array($entorno, ['personal', 'negocio'], true)) {
            Response::error('Entorno inválido. Usa "personal" o "negocio".');
            return;
        }
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
            Response::error('Mes inválido. Usa formato YYYY-MM.');
            return;
        }

        $db = Database::getConnection();

        $stmt = $db->prepare(
            'SELECT c.nombre AS categoria, SUM(t.monto) AS total
             FROM transacciones t
             JOIN categorias c ON c.id = t.categoria_id
             WHERE t.user_id = :user_id
               AND t.entorno = :entorno
               AND t.tipo = "gasto"
               AND DATE_FORMAT(t.fecha, "%Y-%m") = :mes
             GROUP BY c.id, c.nombre
             ORDER BY total DESC'
        );
        $stmt->execute(['user_id' => $userId, 'entorno' => $entorno, 'mes' => $mes]);
        $filas = $stmt->fetchAll();

        $resultado = array_map(function ($f) {
            return [
                'categoria' => $f['categoria'],
                'total' => round((float) $f['total'], 2),
            ];
        }, $filas);

        Response::success([
            'mes' => $mes,
            'entorno' => $entorno,
            'gastos_por_categoria' => $resultado,
        ]);
    }

    /**
     * Devuelve ingresos y gastos mes a mes para los últimos N meses
     * (default 6), terminando en el mes en curso. Pensado para una
     * gráfica de barras/tendencia.
     */
    public static function tendenciaMensual(): void
    {
        $userId = Auth::userId();
        $entorno = $_GET['entorno'] ?? null;
        $meses = (int) ($_GET['meses'] ?? 6);

        if (!in_array($entorno, ['personal', 'negocio'], true)) {
            Response::error('Entorno inválido. Usa "personal" o "negocio".');
            return;
        }
        if ($meses < 1 || $meses > 24) {
            $meses = 6;
        }

        // Generamos la lista de los últimos N meses (YYYY-MM), de más antiguo a más reciente
        $listaMeses = [];
        for ($i = $meses - 1; $i >= 0; $i--) {
            $listaMeses[] = date('Y-m', strtotime("-{$i} months"));
        }
        $mesInicio = $listaMeses[0];

        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT
                DATE_FORMAT(fecha, "%Y-%m") AS mes,
                COALESCE(SUM(CASE WHEN tipo = "ingreso" THEN monto ELSE 0 END), 0) AS ingresos,
                COALESCE(SUM(CASE WHEN tipo = "gasto" THEN monto ELSE 0 END), 0) AS gastos
             FROM transacciones
             WHERE user_id = :user_id
               AND entorno = :entorno
               AND DATE_FORMAT(fecha, "%Y-%m") >= :mes_inicio
             GROUP BY mes'
        );
        $stmt->execute(['user_id' => $userId, 'entorno' => $entorno, 'mes_inicio' => $mesInicio]);
        $filas = $stmt->fetchAll();

        // Indexamos por mes para rellenar huecos (meses sin movimientos)
        $porMes = [];
        foreach ($filas as $f) {
            $porMes[$f['mes']] = [
                'ingresos' => round((float) $f['ingresos'], 2),
                'gastos' => round((float) $f['gastos'], 2),
            ];
        }

        $serie = [];
        foreach ($listaMeses as $mes) {
            $datos = $porMes[$mes] ?? ['ingresos' => 0, 'gastos' => 0];
            $serie[] = [
                'mes' => $mes,
                'ingresos' => $datos['ingresos'],
                'gastos' => $datos['gastos'],
                'balance' => round($datos['ingresos'] - $datos['gastos'], 2),
            ];
        }

        Response::success([
            'entorno' => $entorno,
            'serie' => $serie,
        ]);
    }
}
