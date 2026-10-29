DROP DATABASE IF EXISTS cineverse_db;
CREATE DATABASE cineverse_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cineverse_db;

CREATE TABLE movies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    art TEXT,
    meta VARCHAR(255),
    genre VARCHAR(100),
    duration VARCHAR(50),
    rating DECIMAL(3,1),
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE showtimes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movie_id INT NOT NULL,
    show_date DATE NOT NULL,
    show_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_showtimes_movie FOREIGN KEY (movie_id) REFERENCES movies(id),
    KEY idx_showtimes_movie_id (movie_id),
    KEY idx_showtimes_date (show_date),
    KEY idx_showtimes_time (show_time)
);

CREATE TABLE seats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seat_number VARCHAR(10) NOT NULL UNIQUE,
    row_name CHAR(1) NOT NULL,
    seat_number_numeric INT NOT NULL
);

CREATE TABLE bookings (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    booking_reference VARCHAR(30) NOT NULL UNIQUE,
    movie_id INT NOT NULL,
    showtime_id INT NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(150) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('confirmed','cancelled') DEFAULT 'confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_movie FOREIGN KEY (movie_id) REFERENCES movies(id),
    CONSTRAINT fk_bookings_showtime FOREIGN KEY (showtime_id) REFERENCES showtimes(id)
);

CREATE TABLE booking_seats (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    booking_id BIGINT NOT NULL,
    showtime_id INT NOT NULL,
    seat_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_booking_seats_booking FOREIGN KEY (booking_id) REFERENCES bookings(id),
    CONSTRAINT fk_booking_seats_showtime FOREIGN KEY (showtime_id) REFERENCES showtimes(id),
    CONSTRAINT fk_booking_seats_seat FOREIGN KEY (seat_id) REFERENCES seats(id),
    UNIQUE KEY uk_booking_seats_showtime_seat (showtime_id, seat_id)
);

INSERT INTO seats (seat_number, row_name, seat_number_numeric)
SELECT CONCAT(row_name, seat_number_numeric) AS seat_number, row_name, seat_number_numeric
FROM (
    SELECT 'A' AS row_name, 1 AS seat_number_numeric UNION ALL SELECT 'A',2 UNION ALL SELECT 'A',3 UNION ALL SELECT 'A',4 UNION ALL SELECT 'A',5 UNION ALL SELECT 'A',6 UNION ALL SELECT 'A',7 UNION ALL SELECT 'A',8 UNION ALL SELECT 'A',9 UNION ALL SELECT 'A',10
    UNION ALL SELECT 'B',1 UNION ALL SELECT 'B',2 UNION ALL SELECT 'B',3 UNION ALL SELECT 'B',4 UNION ALL SELECT 'B',5 UNION ALL SELECT 'B',6 UNION ALL SELECT 'B',7 UNION ALL SELECT 'B',8 UNION ALL SELECT 'B',9 UNION ALL SELECT 'B',10
    UNION ALL SELECT 'C',1 UNION ALL SELECT 'C',2 UNION ALL SELECT 'C',3 UNION ALL SELECT 'C',4 UNION ALL SELECT 'C',5 UNION ALL SELECT 'C',6 UNION ALL SELECT 'C',7 UNION ALL SELECT 'C',8 UNION ALL SELECT 'C',9 UNION ALL SELECT 'C',10
    UNION ALL SELECT 'D',1 UNION ALL SELECT 'D',2 UNION ALL SELECT 'D',3 UNION ALL SELECT 'D',4 UNION ALL SELECT 'D',5 UNION ALL SELECT 'D',6 UNION ALL SELECT 'D',7 UNION ALL SELECT 'D',8 UNION ALL SELECT 'D',9 UNION ALL SELECT 'D',10
    UNION ALL SELECT 'E',1 UNION ALL SELECT 'E',2 UNION ALL SELECT 'E',3 UNION ALL SELECT 'E',4 UNION ALL SELECT 'E',5 UNION ALL SELECT 'E',6 UNION ALL SELECT 'E',7 UNION ALL SELECT 'E',8 UNION ALL SELECT 'E',9 UNION ALL SELECT 'E',10
    UNION ALL SELECT 'F',1 UNION ALL SELECT 'F',2 UNION ALL SELECT 'F',3 UNION ALL SELECT 'F',4 UNION ALL SELECT 'F',5 UNION ALL SELECT 'F',6 UNION ALL SELECT 'F',7 UNION ALL SELECT 'F',8 UNION ALL SELECT 'F',9 UNION ALL SELECT 'F',10
    UNION ALL SELECT 'G',1 UNION ALL SELECT 'G',2 UNION ALL SELECT 'G',3 UNION ALL SELECT 'G',4 UNION ALL SELECT 'G',5 UNION ALL SELECT 'G',6 UNION ALL SELECT 'G',7 UNION ALL SELECT 'G',8 UNION ALL SELECT 'G',9 UNION ALL SELECT 'G',10
) AS generated;
