<?php

/**
 * Clase Response
 * Estandariza las respuestas JSON de la API.
 */
class Response
{
    public static function json($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public static function success($data = [], string $message = 'OK'): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], 200);
    }

    public static function error(string $message = 'Error', int $statusCode = 400): void
    {
        self::json([
            'success' => false,
            'message' => $message,
        ], $statusCode);
    }
}
