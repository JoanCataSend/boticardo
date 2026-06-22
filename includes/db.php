<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (Throwable $error) {
    error_log('Boticardo - Error fatal de conexión a BD: ' . $error->getMessage());
    die('Ocurrió un error interno. Por favor, inténtelo de nuevo más tarde.');
}