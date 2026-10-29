<?php
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/../includes/response.php';

try {
    $pdo = getDatabaseConnection();
    $movies = $pdo->query('SELECT id, title, price FROM movies ORDER BY id ASC')->fetchAll();
    $showtimes = $pdo->query('SELECT s.id, m.title AS movie_title, s.show_date, s.show_time FROM showtimes s JOIN movies m ON m.id = s.movie_id ORDER BY s.show_date DESC, s.show_time DESC LIMIT 20')->fetchAll();
    $bookings = $pdo->query('SELECT b.id, b.booking_reference, m.title AS movie_title, b.customer_name, b.total_amount, b.created_at FROM bookings b JOIN movies m ON m.id = b.movie_id ORDER BY b.created_at DESC LIMIT 20')->fetchAll();
} catch (Throwable $e) {
    $movies = [];
    $showtimes = [];
    $bookings = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineVerse Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 32px; background: #f7f4ef; color: #1d2433; }
        h1, h2 { margin-bottom: 12px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; }
        .card { background: white; border: 1px solid #e4ddd0; border-radius: 12px; padding: 18px; box-shadow: 0 4px 18px rgba(0,0,0,0.03); }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 10px 8px; border-bottom: 1px solid #eee; }
        .badge { display: inline-block; background: #ec664d; color: white; border-radius: 999px; padding: 4px 10px; font-size: 12px; }
    </style>
</head>
<body>
    <h1>CineVerse Admin</h1>
    <p><span class="badge">Development / Demo</span> This admin page is for local demonstration and testing only.</p>

    <div class="grid">
        <section class="card">
            <h2>Movies</h2>
            <?php if ($movies): ?>
                <ul>
                    <?php foreach ($movies as $movie): ?>
                        <li><?php echo htmlspecialchars($movie['title']); ?> — ₹<?php echo htmlspecialchars((string)$movie['price']); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No movies found.</p>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2>Showtimes</h2>
            <?php if ($showtimes): ?>
                <table>
                    <tr><th>Movie</th><th>Date</th><th>Time</th></tr>
                    <?php foreach ($showtimes as $show): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($show['movie_title']); ?></td>
                            <td><?php echo htmlspecialchars($show['show_date']); ?></td>
                            <td><?php echo htmlspecialchars($show['show_time']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>No showtimes found.</p>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2>Recent Bookings</h2>
            <?php if ($bookings): ?>
                <table>
                    <tr><th>Ref</th><th>Movie</th><th>Name</th><th>Amount</th></tr>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($booking['booking_reference']); ?></td>
                            <td><?php echo htmlspecialchars($booking['movie_title']); ?></td>
                            <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                            <td>₹<?php echo htmlspecialchars((string)$booking['total_amount']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>No recent bookings found.</p>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>
