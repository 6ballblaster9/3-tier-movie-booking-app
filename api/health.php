<?php

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/db.php';

header('Cache-Control: no-store');

try {
    $pdo = getDatabaseConnection();
    $pdo->query('SELECT 1');
    successResponse([], 200, 'CineVerse API is running');
} catch (Throwable $e) {
    errorResponse('Database connectivity check failed.', 500, ['debug' => 'database connection unavailable']);
}
