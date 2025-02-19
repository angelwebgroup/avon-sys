<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Create users table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) !== TRUE) {
        die("Error creating table: " . $conn->error);
    }

    // Check if default admin exists, if not create one
    $checkAdmin = "SELECT * FROM users WHERE email = 'admin@admin.com'";
    $result = $conn->query($checkAdmin);
    
    if ($result->num_rows == 0) {
        $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $createAdmin = "INSERT INTO users (email, password, name) VALUES ('admin@admin.com', '$defaultPassword', 'Administrator')";
        $conn->query($createAdmin);
    }

    // Verify login
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            header("Location: ../dashboard.php");
            exit();
        }
    }
    
    header("Location: ../index.php?error=1");
    exit();
}
?>
