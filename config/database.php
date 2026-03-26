<?php
// Database connection configuration
function getDatabaseConnection() {
    $host = 'localhost';
    $dbname = 'futsal_booking';
    $username = 'root';
    $password = '';

    try {
        // Create PDO connection
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        
        // Set PDO error mode to exception
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Set default fetch mode to associative array
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        return $pdo;
        
    } catch(PDOException $e) {
        // If database doesn't exist, try to create it
        if (strpos($e->getMessage(), "Unknown database") !== false) {
            try {
                // Connect to MySQL server without database
                $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Create database
                $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                // Reconnect with the database
                $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                
                // Create tables
                createTables($pdo);
                
                return $pdo;
                
            } catch(PDOException $e2) {
                die("Database connection failed: " . $e2->getMessage());
            }
        } else {
            die("Database connection failed: " . $e->getMessage());
        }
    }
}

function createTables($pdo) {
    // Create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            is_admin TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Add is_admin column if it doesn't exist (for existing databases)
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0");
    } catch (Exception $e) {
        // Column already exists, ignore error
    }
    
    // Create bookings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            futsal_name VARCHAR(100) NOT NULL,
            futsal_address VARCHAR(200) NOT NULL,
            booking_date DATE NOT NULL,
            booking_time TIME NOT NULL,
            duration_hours DECIMAL(3,1) NOT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            status ENUM('confirmed', 'cancelled') DEFAULT 'confirmed',
            prepayment_status ENUM('pending', 'paid') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_futsal_datetime (futsal_name, booking_date, booking_time),
            INDEX idx_user_bookings (user_id)
        )
    ");
    
    // Add prepayment_status column if it doesn't exist (for existing databases)
    try {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN prepayment_status ENUM('pending', 'paid') DEFAULT 'pending'");
    } catch (Exception $e) {
        // Column already exists, ignore error
    }
    
    // Insert sample users
    $pdo->exec("
        INSERT IGNORE INTO users (username, email, password, full_name, is_admin) VALUES
        ('admin', 'admin@futsal.com', '\$2y\$10\$l65TvVj9OKRSPDsjyX.weueR2TLBKG9RtoxDVWVlqXbjVPDerDQja', 'Admin User', 1),
        ('john_doe', 'john@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', 0),
        ('jane_smith', 'jane@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Smith', 0)
    ");
}
?>
