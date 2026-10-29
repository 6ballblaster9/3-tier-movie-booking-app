<?php

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/db.php';

applyCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Method not allowed.', 405);
}

$showtimeId = filter_input(INPUT_GET, 'showtime_id', FILTER_VALIDATE_INT);
if ($showtimeId === false || $showtimeId === null || $showtimeId <= 0) {
    errorResponse('A valid showtime id is required.', 400);
}

try {
    $pdo = getDatabaseConnection();
    $showtimeStmt = $pdo->prepare('SELECT id FROM showtimes WHERE id = :showtime_id LIMIT 1');
    $showtimeStmt->execute([':showtime_id' => $showtimeId]);
    if (!$showtimeStmt->fetch()) {
        errorResponse('Showtime not found.', 404);
    }

    $stmt = $pdo->prepare(
        'SELECT s.seat_number AS seat, 
                CASE WHEN bs.seat_id IS NULL THEN 1 ELSE 0 END AS available
         FROM seats s
         LEFT JOIN booking_seats bs ON bs.showtime_id = :showtime_id AND bs.seat_id = s.id
         ORDER BY s.row_name ASC, s.seat_number_numeric ASC'
    );
    $stmt->execute([':showtime_id' => $showtimeId]);

    $seats = array_map(static function (array $seat): array {
        return [
            'seat' => $seat['seat'],
            'available' => (bool) (int) $seat['available'],
        ];
    }, $stmt->fetchAll());

    successResponse($seats);
} catch (Throwable $e) {
    errorResponse('Unable to load seat availability.', 500);
}
