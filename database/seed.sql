USE cineverse_db;

INSERT INTO movies (title, art, meta, genre, duration, rating, price) VALUES
('The Last Horizon', 'THE LAST<br>HORIZON', 'IMAX with Laser · English', 'Sci-fi', '2h 28m', 8.7, 420.00),
('Golden Hour', 'GOLDEN<br>HOUR', 'Dolby Atmos · Hindi', 'Drama', '2h 06m', 8.2, 330.00),
('Blue Current', 'BLUE<br>CURRENT', '3D · English', 'Adventure', '1h 54m', 8.5, 380.00),
('Midnight Run', 'MIDNIGHT<br>RUN', 'Dolby Atmos · Hindi', 'Thriller', '2h 13m', 7.9, 350.00);

-- Generate showtimes for the next 7+ days.
SET @start_date = CURDATE();

INSERT INTO showtimes (movie_id, show_date, show_time)
SELECT m.id,
       DATE_ADD(@start_date, INTERVAL n.num DAY) AS show_date,
       CASE n.slot
           WHEN 1 THEN '10:30:00'
           WHEN 2 THEN '13:45:00'
           WHEN 3 THEN '17:20:00'
           WHEN 4 THEN '20:50:00'
           WHEN 5 THEN '11:15:00'
           WHEN 6 THEN '14:30:00'
           WHEN 7 THEN '18:10:00'
           WHEN 8 THEN '21:30:00'
           WHEN 9 THEN '09:50:00'
           WHEN 10 THEN '12:40:00'
           WHEN 11 THEN '16:15:00'
           WHEN 12 THEN '19:40:00'
           WHEN 13 THEN '10:00:00'
           WHEN 14 THEN '13:20:00'
           WHEN 15 THEN '17:00:00'
           WHEN 16 THEN '20:20:00'
       END AS show_time
FROM movies m
JOIN (
    SELECT 0 AS num, 1 AS slot UNION ALL SELECT 0,2 UNION ALL SELECT 0,3 UNION ALL SELECT 0,4
    UNION ALL SELECT 1,1 UNION ALL SELECT 1,2 UNION ALL SELECT 1,3 UNION ALL SELECT 1,4
    UNION ALL SELECT 2,5 UNION ALL SELECT 2,6 UNION ALL SELECT 2,7 UNION ALL SELECT 2,8
    UNION ALL SELECT 3,9 UNION ALL SELECT 3,10 UNION ALL SELECT 3,11 UNION ALL SELECT 3,12
    UNION ALL SELECT 4,13 UNION ALL SELECT 4,14 UNION ALL SELECT 4,15 UNION ALL SELECT 4,16
    UNION ALL SELECT 5,1 UNION ALL SELECT 5,2 UNION ALL SELECT 5,3 UNION ALL SELECT 5,4
    UNION ALL SELECT 6,5 UNION ALL SELECT 6,6 UNION ALL SELECT 6,7 UNION ALL SELECT 6,8
) n
WHERE CASE m.title
    WHEN 'The Last Horizon' THEN slot IN (1,2,3,4)
    WHEN 'Golden Hour' THEN slot IN (5,6,7,8)
    WHEN 'Blue Current' THEN slot IN (9,10,11,12)
    WHEN 'Midnight Run' THEN slot IN (13,14,15,16)
END;

-- Initial taken seats for the first date showtimes.
SET @show1 = (SELECT id FROM showtimes WHERE movie_id = 1 ORDER BY show_date, show_time LIMIT 1);
SET @show2 = (SELECT id FROM showtimes WHERE movie_id = 2 ORDER BY show_date, show_time LIMIT 1);
SET @show3 = (SELECT id FROM showtimes WHERE movie_id = 3 ORDER BY show_date, show_time LIMIT 1);
SET @show4 = (SELECT id FROM showtimes WHERE movie_id = 4 ORDER BY show_date, show_time LIMIT 1);

INSERT INTO bookings (booking_reference, movie_id, showtime_id, customer_name, customer_email, total_amount)
VALUES
('CV-INIT-001', 1, @show1, 'Demo Guest', 'guest@example.com', 1260.00),
('CV-INIT-002', 2, @show2, 'Demo Guest', 'guest@example.com', 990.00),
('CV-INIT-003', 3, @show3, 'Demo Guest', 'guest@example.com', 1140.00),
('CV-INIT-004', 4, @show4, 'Demo Guest', 'guest@example.com', 1050.00);

INSERT INTO booking_seats (booking_id, showtime_id, seat_id, price)
SELECT b.id,
       b.showtime_id,
       s.id,
       m.price
FROM bookings b
JOIN movies m ON m.id = b.movie_id
JOIN seats s ON s.seat_number IN ('A3','A7','B2','B8','C4','C5','C6','D1','D9','E3','E7','F5','F6','G2','G8')
WHERE b.booking_reference = 'CV-INIT-001' AND b.showtime_id = @show1
UNION ALL
SELECT b.id, b.showtime_id, s.id, m.price
FROM bookings b
JOIN movies m ON m.id = b.movie_id
JOIN seats s ON s.seat_number IN ('A3','A7','B2','B8','C4','C5','C6','D1','D9','E3','E7','F5','F6','G2','G8')
WHERE b.booking_reference = 'CV-INIT-002' AND b.showtime_id = @show2
UNION ALL
SELECT b.id, b.showtime_id, s.id, m.price
FROM bookings b
JOIN movies m ON m.id = b.movie_id
JOIN seats s ON s.seat_number IN ('A3','A7','B2','B8','C4','C5','C6','D1','D9','E3','E7','F5','F6','G2','G8')
WHERE b.booking_reference = 'CV-INIT-003' AND b.showtime_id = @show3
UNION ALL
SELECT b.id, b.showtime_id, s.id, m.price
FROM bookings b
JOIN movies m ON m.id = b.movie_id
JOIN seats s ON s.seat_number IN ('A3','A7','B2','B8','C4','C5','C6','D1','D9','E3','E7','F5','F6','G2','G8')
WHERE b.booking_reference = 'CV-INIT-004' AND b.showtime_id = @show4;
