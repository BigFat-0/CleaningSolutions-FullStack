<?php
// v1/setup_db.php

$host = 'h880oo80sksccwgggk8okwkk';
$db   = 'default';
$user = 'user1';
$pass = 'KIS710SnmXeRMVqv6zhJO4gzkvUUG1qLyO1n8Rn0HkNFAMAnf3OoqOcWGjdfdVvQ';
$port = "3306";
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;port=$port;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    $admin_pass_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // password123
    $admin_sec_hash = password_hash('123', PASSWORD_DEFAULT);

    // SQL to reset database
    $sql = "
    SET FOREIGN_KEY_CHECKS = 0;
    DROP TABLE IF EXISTS bookings;
    DROP TABLE IF EXISTS users;

    CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        phone_number VARCHAR(20) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        security_question VARCHAR(255) NOT NULL,
        security_answer_hash VARCHAR(255) NOT NULL,
        billing_address TEXT NOT NULL,
        role VARCHAR(20) DEFAULT 'customer',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        job_description TEXT NOT NULL,
        service_address TEXT NOT NULL,
        quoted_price DECIMAL(10, 2) DEFAULT NULL,
        actual_bill DECIMAL(10, 2) DEFAULT NULL,
        scheduled_date DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('awaiting_quote', 'quoted', 'confirmed', 'completed', 'cancelled', 'rejected') DEFAULT 'awaiting_quote',
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    -- Seed Admin: admin@cleaning.com / password123
    INSERT INTO users (first_name, last_name, email, phone_number, password_hash, security_question, security_answer_hash, billing_address, role) VALUES 
    ('Admin', 'User', 'admin@cleaning.com', '07700900000', '$admin_pass_hash', 'Secret', '$admin_sec_hash', 'Admin HQ', 'staff');

    SET FOREIGN_KEY_CHECKS = 1;
    ";

    $pdo->exec($sql);
    echo "Database Tables Created Successfully";

} catch (\PDOException $e) {
    echo "Database Setup Failed: " . $e->getMessage();
    exit(1);
}
?>
