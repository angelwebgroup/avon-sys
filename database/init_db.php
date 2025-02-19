<?php
require_once dirname(__DIR__) . '/config/database.php';

// Read and execute SQL file
$sql = file_get_contents(__DIR__ . '/schema.sql');

if ($conn->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    
    echo "Database schema created successfully!\n";
    
    // Create default admin user
    $username = 'admin';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $email = 'admin@example.com';
    
    $stmt = $conn->prepare("INSERT IGNORE INTO users (username, password, email, role) VALUES (?, ?, ?, 'admin')");
    $stmt->bind_param("sss", $username, $password, $email);
    
    if ($stmt->execute()) {
        echo "Default admin user created successfully!\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
    } else {
        echo "Error creating admin user: " . $stmt->error . "\n";
    }
    
    $stmt->close();
} else {
    echo "Error creating database schema: " . $conn->error . "\n";
}

$conn->close();
