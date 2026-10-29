<?php

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Method not allowed.', 405);
}

try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->query('SELECT id, title, art, meta, genre, duration, rating, price FROM movies ORDER BY id ASC');
    $movies = $stmt->fetchAll();
    successResponse($movies);
} catch (Throwable $e) {
    errorResponse('Unable to load movies.', 500);
}
