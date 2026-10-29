<?php

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/db.php';

applyCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method not allowed.', 405);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    errorResponse('Invalid JSON input.', 400);
}

$showtimeId = validateShowtimeId($data['showtime_id'] ?? null);
$customerName = validateCustomerName($data['customer_name'] ?? null);
$email = validateEmail($data['customer_email'] ?? null);

if ($showtimeId === null) {
    errorResponse('Please select a valid showtime.', 400);
}

if (!$customerName['valid']) {
    errorResponse($customerName['message'], 400);
}

if (!$email['valid']) {
    errorResponse($email['message'], 400);
}

$requestedSeats = $data['seats'] ?? [];
if (!is_array($requestedSeats)) {
    errorResponse('Seat selection is invalid.', 400);
}

$normalizedSeats = [];
foreach ($requestedSeats as $seatValue) {
    $seat = normalizeSeatNumber($seatValue);
    if ($seat === null) {
        errorResponse('One or more selected seats are invalid.', 400);
    }
    $normalizedSeats[] = $seat;
}

if (count($normalizedSeats) === 0) {
    errorResponse('Please select at least one seat.', 400);
}

if (count($normalizedSeats) > 6) {
    errorResponse('You can select a maximum of 6 seats.', 400);
}

$uniqueSeatList = array_values(array_unique($normalizedSeats));
if (count($uniqueSeatList) !== count($normalizedSeats)) {
    errorResponse('Duplicate seats were selected. Please choose unique seats.', 400);
}

$pdo = null;

try {
    $pdo = getDatabaseConnection();
    $pdo->beginTransaction();

    $showtimeStmt = $pdo->prepare('SELECT id, movie_id, show_time FROM showtimes WHERE id = :id LIMIT 1 FOR UPDATE');
    $showtimeStmt->execute([':id' => $showtimeId]);
    $showtime = $showtimeStmt->fetch();

    if (!$showtime) {
        $pdo->rollBack();
        errorResponse('Selected showtime is no longer available.', 404);
    }

    $movieStmt = $pdo->prepare('SELECT id, title, price FROM movies WHERE id = :movie_id LIMIT 1 FOR UPDATE');
    $movieStmt->execute([':movie_id' => $showtime['movie_id']]);
    $movie = $movieStmt->fetch();

    if (!$movie) {
        $pdo->rollBack();
        errorResponse('Movie for this showtime was not found.', 404);
    }

    $seatIds = [];
    $seatMap = [];
    if ($uniqueSeatList !== []) {
        $in = implode(',', array_fill(0, count($uniqueSeatList), '?'));
        $seatSql = $pdo->prepare('SELECT id, seat_number FROM seats WHERE seat_number IN (' . $in . ')');
        $seatSql->execute($uniqueSeatList);
        $seatRows = $seatSql->fetchAll();

        foreach ($seatRows as $row) {
            $seatMap[strtoupper($row['seat_number'])] = (int) $row['id'];
        }

        foreach ($uniqueSeatList as $seatNumber) {
            if (!isset($seatMap[$seatNumber])) {
                $pdo->rollBack();
                errorResponse('One or more selected seats do not exist.', 400);
            }
            $seatIds[] = $seatMap[$seatNumber];
        }
    }

    if ($seatIds !== []) {
        $seatIn = implode(',', array_fill(0, count($seatIds), '?'));
        $checkStmt = $pdo->prepare('SELECT seat_id FROM booking_seats WHERE showtime_id = ? AND seat_id IN (' . $seatIn . ') FOR UPDATE');
        $params = [$showtimeId, ...$seatIds];
        $checkStmt->execute($params);
        $takenSeats = $checkStmt->fetchAll(PDO::FETCH_COLUMN);

        if ($takenSeats !== []) {
            $pdo->rollBack();
            errorResponse('One or more selected seats are no longer available.', 409);
        }
    }

    $totalAmount = count($uniqueSeatList) * (float) $movie['price'];
    $reference = 'CV-' . strtoupper(bin2hex(random_bytes(6)));
    $bookingStmt = $pdo->prepare(
        'INSERT INTO bookings (booking_reference, movie_id, showtime_id, customer_name, customer_email, total_amount) VALUES (:reference, :movie_id, :showtime_id, :customer_name, :customer_email, :total_amount)'
    );
    $bookingStmt->execute([
        ':reference' => $reference,
        ':movie_id' => (int) $movie['id'],
        ':showtime_id' => $showtimeId,
        ':customer_name' => $customerName['value'],
        ':customer_email' => $email['value'],
        ':total_amount' => number_format((float) $totalAmount, 2, '.', ''),
    ]);

    $bookingId = (int) $pdo->lastInsertId();
    $bookingSeatStmt = $pdo->prepare(
        'INSERT INTO booking_seats (booking_id, showtime_id, seat_id, price) VALUES (:booking_id, :showtime_id, :seat_id, :price)'
    );

    foreach ($uniqueSeatList as $seatNumber) {
        $bookingSeatStmt->execute([
            ':booking_id' => $bookingId,
            ':showtime_id' => $showtimeId,
            ':seat_id' => $seatMap[$seatNumber],
            ':price' => number_format((float) $movie['price'], 2, '.', ''),
        ]);
    }

    $pdo->commit();

    $showtimeText = (new DateTime($showtime['show_time']))->format('h:i A');
    successResponse([
        'booking_reference' => $reference,
        'movie' => $movie['title'],
        'showtime' => $showtimeText,
        'seats' => $uniqueSeatList,
        'total_amount' => (float) $totalAmount,
    ], 201);
} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    errorResponse('Booking failed. Please try again.', 500);
}
