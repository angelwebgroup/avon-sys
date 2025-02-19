<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<div style='font-family: Arial; padding: 20px;'>";

try {
    // Step 1: Drop all existing tables
    echo "<h3>Step 1: Dropping existing tables...</h3>";
    $conn->query("SET FOREIGN_KEY_CHECKS=0");
    $tables = ['user_permissions', 'permissions', 'users', 'customers', 'quotes', 'quote_items', 'purchase_orders', 'inquiries'];
    foreach ($tables as $table) {
        $conn->query("DROP TABLE IF EXISTS $table");
        echo "Dropped table: $table<br>";
    }
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
    
    // Step 2: Create tables
    echo "<h3>Step 2: Creating new tables...</h3>";
    
    // Users table
    $conn->query("
        CREATE TABLE users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            role ENUM('admin', 'sales_rep', 'manager') NOT NULL,
            status ENUM('pending', 'approved', 'rejected', 'inactive') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "Created users table<br>";
    
    // Create admin user
    $username = 'admin';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $email = 'admin@example.com';
    
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, status) VALUES (?, ?, ?, 'admin', 'approved')");
    $stmt->bind_param("sss", $username, $password, $email);
    $stmt->execute();
    echo "Created admin user<br>";
    
    // Test password verification
    $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (password_verify('admin123', $user['password'])) {
        echo "<div style='color: green;'>Password verification test successful!</div>";
    } else {
        echo "<div style='color: red;'>Password verification test failed!</div>";
    }
    
    echo "<h3>Setup Complete!</h3>";
    echo "<div style='background: #e8f5e9; padding: 20px; margin: 20px 0; border-radius: 5px;'>";
    echo "<strong>Admin Login Credentials:</strong><br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
    echo "</div>";
    
    echo "<h3>Test Links:</h3>";
    echo "<ul>";
    echo "<li><a href='index.php'>Main Login Page</a></li>";
    echo "<li><a href='test_login.php'>Login Test Page</a></li>";
    echo "</ul>";
    
    echo "<h3>Troubleshooting:</h3>";
    echo "<ul>";
    echo "<li>Clear your browser cache</li>";
    echo "<li>Try using an incognito/private window</li>";
    echo "<li>Check PHP error logs at: " . ini_get('error_log') . "</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>";
    echo "<h3>Error:</h3>";
    echo $e->getMessage();
    echo "</div>";
}

echo "</div>";
