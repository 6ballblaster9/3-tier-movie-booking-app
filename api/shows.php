<?php

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Method not allowed.', 405);
}

$movieId = filter_input(INPUT_GET, 'movie_id', FILTER_VALIDATE_INT);
$date = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

if ($movieId === false || $movieId === null || $movieId <= 0) {
    errorResponse('A valid movie id is required.', 400);
}

if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    errorResponse('A valid date is required in YYYY-MM-DD format.', 400);
}

try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare('SELECT id, movie_id, show_date, show_time FROM showtimes WHERE movie_id = :movie_id AND show_date = :show_date ORDER BY show_time ASC');
    $stmt->execute([
        ':movie_id' => $movieId,
        ':show_date' => $date,
    ]);

    $shows = $stmt->fetchAll();
    $data = array_map(static function (array $show): array {
        $time = new DateTime($show['show_time']);
        return [
            'id' => (int) $show['id'],
            'movie_id' => (int) $show['movie_id'],
            'show_date' => $show['show_date'],
            'show_time' => $show['show_time'],
            'formatted_time' => $time->format('h:i A'),
        ];
    }, $shows);

    successResponse($data);
} catch (Throwable $e) {
    errorResponse('Unable to load showtimes.', 500);
}
