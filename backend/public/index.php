<?php

/**
 * Punto de entrada único de la API.
 * Todas las peticiones (gracias al .htaccess / apache-config.conf) llegan aquí.
 */

require_once __DIR__ . '/../src/Response.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/CategoriasController.php';
require_once __DIR__ . '/../src/controllers/TransaccionesController.php';
require_once __DIR__ . '/../src/controllers/DashboardController.php';

// --- CORS (solo relevante si en algún momento separas dominios) ---
$allowedOrigin = getenv('FRONTEND_ORIGIN') ?: 'http://localhost:5173';
header("Access-Control-Allow-Origin: {$allowedOrigin}");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Las peticiones OPTIONS (preflight) se responden vacías y ya
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

Auth::startSession();

// --- Parseo de la ruta ---
// Ejemplo: /api/transacciones -> ["api", "transacciones"]
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = array_values(array_filter(explode('/', $uri)));

// Quitamos el segmento "api" si existe (ej: /api/login -> login)
if (isset($segments[0]) && $segments[0] === 'api') {
    array_shift($segments);
}

$resource = $segments[0] ?? '';
$idOrAction = $segments[1] ?? null;

$method = $_SERVER['REQUEST_METHOD'];

// Body JSON (para POST/PUT)
$body = [];
if (in_array($method, ['POST', 'PUT'], true)) {
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true) ?? [];
}

try {
    switch ($resource) {
        case 'register':
            if ($method === 'POST') {
                AuthController::register($body);
            } else {
                Response::error('Método no permitido.', 405);
            }
            break;

        case 'login':
            if ($method === 'POST') {
                AuthController::login($body);
            } else {
                Response::error('Método no permitido.', 405);
            }
            break;

        case 'logout':
            if ($method === 'POST') {
                AuthController::logout();
            } else {
                Response::error('Método no permitido.', 405);
            }
            break;

        case 'me':
            if ($method === 'GET') {
                AuthController::me();
            } else {
                Response::error('Método no permitido.', 405);
            }
            break;

        case 'categorias':
            Auth::requireAuth();
            if ($method === 'GET') {
                CategoriasController::index();
            } elseif ($method === 'POST') {
                CategoriasController::store($body);
            } elseif ($method === 'DELETE' && $idOrAction) {
                CategoriasController::destroy((int) $idOrAction);
            } else {
                Response::error('Método no permitido.', 405);
            }
            break;

        case 'transacciones':
            Auth::requireAuth();
            if ($method === 'GET') {
                TransaccionesController::index();
            } elseif ($method === 'POST') {
                TransaccionesController::store($body);
            } elseif ($method === 'DELETE' && $idOrAction) {
                TransaccionesController::destroy((int) $idOrAction);
            } else {
                Response::error('Método no permitido.', 405);
            }
            break;

        case 'dashboard':
            Auth::requireAuth();
            if ($method !== 'GET') {
                Response::error('Método no permitido.', 405);
                break;
            }
            if ($idOrAction === 'balance') {
                DashboardController::balance();
            } elseif ($idOrAction === 'gastos-por-categoria') {
                DashboardController::gastosPorCategoria();
            } elseif ($idOrAction === 'tendencia-mensual') {
                DashboardController::tendenciaMensual();
            } else {
                Response::error('Ruta no encontrada.', 404);
            }
            break;

        default:
            Response::error('Ruta no encontrada.', 404);
            break;
    }
} catch (Throwable $e) {
    // Captura cualquier error inesperado para nunca devolver HTML de error de PHP
    Response::error('Error interno del servidor: ' . $e->getMessage(), 500);
}
