-- Futsal Booking System Database Setup
-- Run this script in MySQL to create the database and tables

CREATE DATABASE IF NOT EXISTS futsal_booking;
USE futsal_booking;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bookings table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    futsal_name VARCHAR(100) NOT NULL,
    futsal_address VARCHAR(200) NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    duration_hours DECIMAL(3,1) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    prepayment_amount DECIMAL(10,2) DEFAULT 100.00,
    prepayment_status ENUM('pending', 'paid') DEFAULT 'pending',
    status ENUM('confirmed', 'cancelled') DEFAULT 'confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_futsal_datetime (futsal_name, booking_date, booking_time),
    INDEX idx_user_bookings (user_id)
);

-- Insert sample users (password: 'password123' hashed)
INSERT IGNORE INTO users (username, email, password, full_name) VALUES
('admin', 'admin@futsal.com', '$2y$12$FdwhGhu/SV4Ku6MmupOpdu8QjZ5WUyz08lRozKDevbbMd1qc3POi2', 'Admin User'),
('john_doe', 'john@example.com', '$2y$12$FdwhGhu/SV4Ku6MmupOpdu8QjZ5WUyz08lRozKDevbbMd1qc3POi2', 'John Doe'),
('jane_smith', 'jane@example.com', '$2y$12$FdwhGhu/SV4Ku6MmupOpdu8QjZ5WUyz08lRozKDevbbMd1qc3POi2', 'Jane Smith');

-- Show the created tables
SHOW TABLES;
